# TeleOps Scheduling Engine - Feature Specifications

## Overview

This document contains detailed specifications for MVP (Must-have) features. Features are grouped by functional area and include user stories, acceptance criteria, data requirements, and technical notes.

-----

# AREA 1: User & Identity Management

-----

## Feature Spec: F1 - Operator Registration

**ID**: F1
**Priority**: Must
**Status**: Draft

### Overview

Operators (remote robot controllers) can create accounts on the platform, providing their personal details, location, timezone, and basic equipment information. This is the entry point for the operator side of the marketplace.

### User Stories

#### Primary Story

> As a prospective operator, I want to register an account so that I can offer my teleoperation services on the platform.

#### Secondary Stories

> As a prospective operator, I want to specify my timezone and location so that I can be matched to appropriate jobs.

> As a platform admin, I want new registrations to require email verification so that we have valid contact information.

### Acceptance Criteria

- [ ] Given a visitor on the registration page, when they submit valid registration data, then an unverified account is created
- [ ] Given a new registration, when the account is created, then a verification email is sent
- [ ] Given a verification email, when the user clicks the link, then their account becomes verified
- [ ] Given registration, when email already exists, then show "email already registered" error
- [ ] Given registration, when required fields are missing, then show field-specific validation errors
- [ ] Given a verified operator, when they log in, then they can access operator features
- [ ] Given an unverified operator, when they try to accept jobs, then they are prompted to verify first

### UI/UX Requirements

- Registration form: email, password (with confirmation), full name, country, timezone
- Password strength indicator
- Clear error messages inline with fields
- Verification email should include platform name and clear call-to-action

### Business Rules

1. **Email uniqueness**: One account per email address
1. **Password requirements**: Minimum 8 characters, at least one number and one letter
1. **Timezone required**: Must select from standard IANA timezone list
1. **Country required**: Must select from ISO country list
1. **Verification expiry**: Verification links expire after 24 hours

### Edge Cases & Error Handling

|Scenario                      |Expected Behavior                                                     |
|------------------------------|----------------------------------------------------------------------|
|Duplicate email               |"An account with this email already exists. Log in or reset password."|
|Invalid email format          |"Please enter a valid email address"                                  |
|Weak password                 |"Password must be at least 8 characters with letters and numbers"     |
|Verification link expired     |"This link has expired. Request a new verification email."            |
|Verification link already used|"Your email is already verified. Please log in."                      |

### Data Requirements

- **Inputs**: email, password, full_name, country_code, timezone_id
- **Outputs**: User record, verification token
- **Storage**: `operators` table, `email_verifications` table

### Technical Notes

```php
// Entity: Operator
#[ORM\Entity(repositoryClass: OperatorRepository::class)]
#[ORM\Table(name: 'operators')]
class Operator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password; // hashed

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(length: 2)]
    private string $countryCode; // ISO 3166-1 alpha-2

    #[ORM\Column(length: 64)]
    private string $timezoneId; // IANA timezone

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(enumType: OperatorStatus::class)]
    private OperatorStatus $status = OperatorStatus::PENDING;
}

enum OperatorStatus: string
{
    case PENDING = 'pending';      // Registered, not verified
    case ACTIVE = 'active';        // Verified and approved
    case SUSPENDED = 'suspended';  // Temporarily disabled
    case DEACTIVATED = 'deactivated'; // Permanently disabled
}
```

### Out of Scope

- Social login (Google, LinkedIn) - Phase 2
- Profile photo upload - Phase 2
- Mobile number verification - Phase 2

### Dependencies

- Depends on: Nothing (foundation feature)
- Blocks: F3 (Authentication), F6 (Skill Profiles), F9 (Availability)

-----

## Feature Spec: F2 - Client Registration

**ID**: F2
**Priority**: Must
**Status**: Draft

### Overview

Businesses (robot owners) can create accounts to post jobs and manage their robot workforce coverage. Registration captures company details and primary contact.

### User Stories

#### Primary Story

> As a business owner, I want to register my company so that I can find operators for my robots.

### Acceptance Criteria

- [ ] Given a visitor, when they submit valid company registration, then a client account is created
- [ ] Given registration, when company name is provided, then create organization record
- [ ] Given registration, when primary contact details provided, then create admin user for organization
- [ ] Given a new client, when verified, then they can add sites and robots
- [ ] Given registration, when required fields missing, then show validation errors

### Business Rules

1. **Organization model**: Clients are organizations with multiple users
1. **Primary contact**: First user becomes organization admin
1. **Email verification**: Required before accessing platform features
1. **Company name**: Must be provided, doesn't need to be unique

### Data Requirements

```php
#[ORM\Entity(repositoryClass: ClientOrganizationRepository::class)]
#[ORM\Table(name: 'client_organizations')]
class ClientOrganization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $companyName;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(enumType: ClientStatus::class)]
    private ClientStatus $status = ClientStatus::PENDING;

    #[ORM\OneToMany(targetEntity: ClientUser::class, mappedBy: 'organization')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Site::class, mappedBy: 'organization')]
    private Collection $sites;
}

#[ORM\Entity(repositoryClass: ClientUserRepository::class)]
#[ORM\Table(name: 'client_users')]
class ClientUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClientOrganization::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ClientOrganization $organization;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(enumType: ClientUserRole::class)]
    private ClientUserRole $role = ClientUserRole::MEMBER;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;
}

enum ClientUserRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
}
```

### Dependencies

- Depends on: Nothing (foundation feature)
- Blocks: F3 (Authentication), F11 (Sites), F12 (Robots)

-----

## Feature Spec: F3 - Authentication

**ID**: F3
**Priority**: Must
**Status**: Draft

### Overview

JWT-based authentication system supporting both operators and clients, with secure token refresh mechanism.

### User Stories

> As a registered user, I want to log in securely so that I can access my account.

> As a logged-in user, I want my session to persist across browser refreshes so that I don't have to log in repeatedly.

> As a user, I want to log out so that my session is terminated.

### Acceptance Criteria

- [ ] Given valid credentials, when user logs in, then receive JWT access token (15 min) and refresh token (7 days)
- [ ] Given valid access token, when making API request, then request is authenticated
- [ ] Given expired access token with valid refresh token, when client requests refresh, then new tokens issued
- [ ] Given invalid credentials, when user logs in, then return 401 with generic error
- [ ] Given logged-in user, when they log out, then refresh token is invalidated
- [ ] Given 5 failed login attempts, when 6th attempt made, then account locked for 15 minutes

### Technical Notes

```php
// JWT Configuration in config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 900  # 15 minutes

// Custom UserProvider to handle both Operator and ClientUser
class MultiTypeUserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Try operator first, then client user
        // Include user type in JWT payload for downstream checks
    }
}
```

### Dependencies

- Depends on: F1 (Operator Registration), F2 (Client Registration)
- Blocks: All authenticated features

-----

## Feature Spec: F4 - Role-Based Access Control

**ID**: F4
**Priority**: Must
**Status**: Draft

### Overview

Different user types (Operators, Clients, Admins) have different permissions and access to platform features.

### User Stories

> As a platform owner, I want users to only access features appropriate to their role so that the platform is secure and organized.

### Acceptance Criteria

- [ ] Given an operator, when accessing client management features, then return 403 Forbidden
- [ ] Given a client, when accessing operator profiles (beyond public info), then return 403
- [ ] Given an admin, when accessing any feature, then access is granted
- [ ] Given a client member, when trying to delete robots, then denied (admin-only action)

### Business Rules

|Role          |Can Access                                                 |
|--------------|-----------------------------------------------------------|
|Operator      |Own profile, availability, assigned shifts, job marketplace|
|Client Member |View org sites, robots, schedules                          |
|Client Admin  |All client member + manage users, sites, robots, jobs      |
|Platform Admin|Everything + user management, system settings              |

### Technical Notes

```php
// Symfony Security Voters for fine-grained access
class ShiftVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['VIEW', 'ACCEPT', 'CANCEL'])
            && $subject instanceof Shift;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $shift = $subject;

        return match($attribute) {
            'VIEW' => $this->canView($user, $shift),
            'ACCEPT' => $this->canAccept($user, $shift),
            'CANCEL' => $this->canCancel($user, $shift),
            default => false,
        };
    }
}
```

### Dependencies

- Depends on: F3 (Authentication)
- Blocks: All protected features

-----

# AREA 2: Operator Capabilities

-----

## Feature Spec: F6 - Skill Profiles

**ID**: F6
**Priority**: Must
**Status**: Draft

### Overview

Operators define their skills including task types they can perform, robot models they're trained on, and proficiency levels. This enables accurate job matching.

### User Stories

> As an operator, I want to list my skills so that I'm matched to jobs I'm qualified for.

> As a client, I want to see operator skill profiles so that I can trust they're qualified.

### Acceptance Criteria

- [ ] Given an operator, when they add a skill, then it appears on their profile
- [ ] Given skill addition, when selecting skill type, then show predefined skill categories
- [ ] Given a skill, when operator sets proficiency level, then store 1-5 rating
- [ ] Given job matching, when checking skills, then operator must have ALL required skills
- [ ] Given an operator profile, when client views it, then see skills with proficiency levels

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'skill_definitions')]
class SkillDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;  // e.g., "Shelf Stocking", "Precision Assembly"

    #[ORM\Column(length: 50)]
    private string $category;  // e.g., "task_type", "robot_model", "environment"

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;
}

#[ORM\Entity]
#[ORM\Table(name: 'operator_skills')]
class OperatorSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\ManyToOne(targetEntity: SkillDefinition::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SkillDefinition $skill;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $proficiencyLevel;  // 1-5

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $addedAt;
}
```

### Business Rules

1. **Proficiency levels**: 1=Beginner, 2=Basic, 3=Intermediate, 4=Advanced, 5=Expert
1. **Skill categories**: task_type, robot_model, environment, software
1. **No duplicates**: Operator can't add same skill twice

### Dependencies

- Depends on: F1 (Operator Registration)
- Blocks: F21 (Skill Matching)

-----

## Feature Spec: F7 - Certification Tracking

**ID**: F7
**Priority**: Must
**Status**: Draft

### Overview

Track operator certifications (equipment training, safety certifications) with issue dates and expiry. Expired certifications prevent job matching.

### User Stories

> As an operator, I want to record my certifications so that clients know I'm qualified.

> As a platform, I want to track certification expiry so that only certified operators are matched.

### Acceptance Criteria

- [ ] Given an operator, when they add a certification, then record issuer, date, expiry
- [ ] Given a certification with expiry date, when date passes, then certification becomes "expired"
- [ ] Given expired certification, when matching to jobs requiring it, then operator excluded
- [ ] Given certification approaching expiry (30 days), when operator logs in, then show warning
- [ ] Given a client, when viewing operator profile, then see certification status

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'certification_types')]
class CertificationType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;  // e.g., "ExoArm-7 Operator Certification"

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $validityMonths = null;  // How long cert is valid, null = no expiry

    #[ORM\Column]
    private bool $required = false;  // Required for any job?
}

#[ORM\Entity]
#[ORM\Table(name: 'operator_certifications')]
class OperatorCertification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\ManyToOne(targetEntity: CertificationType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CertificationType $certificationType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificateNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(enumType: CertificationStatus::class)]
    private CertificationStatus $status = CertificationStatus::PENDING_VERIFICATION;

    public function isValid(): bool
    {
        if ($this->status !== CertificationStatus::VERIFIED) {
            return false;
        }
        if ($this->expiryDate === null) {
            return true;
        }
        return $this->expiryDate >= new \DateTimeImmutable('today');
    }
}

enum CertificationStatus: string
{
    case PENDING_VERIFICATION = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
}
```

### Dependencies

- Depends on: F1 (Operator Registration)
- Blocks: F21 (Skill Matching)

-----

## Feature Spec: F9 - Availability Management

**ID**: F9
**Priority**: Must
**Status**: Draft

### Overview

Operators set their recurring weekly availability (regular working hours) plus one-off overrides (holidays, sick days, extra availability). This is the foundation for shift matching.

### User Stories

> As an operator, I want to set my regular working hours so that I'm only offered shifts when I'm available.

> As an operator, I want to mark specific dates as unavailable so that I'm not scheduled during holidays.

> As an operator, I want to add extra availability for a specific date so that I can pick up additional shifts.

### Acceptance Criteria

- [ ] Given an operator, when setting weekly availability, then can specify hours for each day
- [ ] Given weekly availability, when spanning midnight, then handle correctly (e.g., night shift)
- [ ] Given recurring availability, when adding date override, then override takes precedence
- [ ] Given availability, when querying for a specific date/time, then correctly compute available status
- [ ] Given timezone, when storing availability, then store in operator's local timezone
- [ ] Given no availability set, when matching, then operator is considered unavailable

### UI/UX Requirements

- Weekly calendar grid showing available hours
- Click-and-drag to set time blocks
- Color coding: available (green), unavailable (gray), override (yellow)
- Clear indication of timezone

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'operator_weekly_availability')]
class OperatorWeeklyAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $dayOfWeek;  // 0=Sunday, 1=Monday, etc.

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;  // Local time

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;  // Local time

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $effectiveUntil = null;
}

#[ORM\Entity]
#[ORM\Table(name: 'operator_availability_overrides')]
class OperatorAvailabilityOverride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(enumType: OverrideType::class)]
    private OverrideType $type;

    // For AVAILABLE overrides, specify the hours
    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;
}

enum OverrideType: string
{
    case UNAVAILABLE = 'unavailable';  // Whole day blocked
    case AVAILABLE = 'available';      // Additional availability
    case MODIFIED = 'modified';        // Different hours than usual
}
```

### Service Layer

```php
class AvailabilityService
{
    public function isOperatorAvailable(
        Operator $operator,
        \DateTimeImmutable $startUtc,
        \DateTimeImmutable $endUtc
    ): bool {
        // 1. Convert UTC times to operator's timezone
        // 2. Check for overrides on the date(s)
        // 3. Fall back to weekly availability
        // 4. Return true only if entire period is covered
    }

    public function getAvailableOperatorsForPeriod(
        \DateTimeImmutable $startUtc,
        \DateTimeImmutable $endUtc,
        array $requiredSkillIds = []
    ): array {
        // Complex query joining operators, availability, skills
    }
}
```

### Dependencies

- Depends on: F1 (Operator Registration)
- Blocks: F22 (Availability Matching), F17 (Shift Assignment)

-----

## Feature Spec: F10 - Location & Latency Profile

**ID**: F10
**Priority**: Must
**Status**: Draft

### Overview

Store operator location and measure/estimate network latency to different robot deployment regions. This enables matching operators to robots within acceptable latency thresholds.

### User Stories

> As an operator, I want my location recorded so that I'm matched to jobs where I have good network connectivity.

> As the platform, I want to measure operator latency to robot regions so that we only match operators who can control robots in real-time.

### Acceptance Criteria

- [ ] Given operator registration, when location provided, then store country and optionally city
- [ ] Given an operator, when they run a latency test, then record RTT to each robot region
- [ ] Given latency data older than 7 days, when matching, then prompt operator to re-test
- [ ] Given a job requiring <100ms latency, when matching, then only include operators meeting threshold
- [ ] Given operator without latency data, when matching, then exclude or warn

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'robot_regions')]
class RobotRegion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $code;  // e.g., "ap-northeast-1", "eu-west-1"

    #[ORM\Column(length: 100)]
    private string $name;  // e.g., "Tokyo", "Ireland"

    #[ORM\Column(length: 255)]
    private string $latencyTestEndpoint;  // URL for ping test
}

#[ORM\Entity]
#[ORM\Table(name: 'operator_latency_measurements')]
class OperatorLatencyMeasurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\ManyToOne(targetEntity: RobotRegion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private RobotRegion $region;

    #[ORM\Column(type: Types::INTEGER)]
    private int $latencyMs;  // Round-trip time in milliseconds

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $measuredAt;

    public function isFresh(int $maxAgeDays = 7): bool
    {
        $threshold = new \DateTimeImmutable("-{$maxAgeDays} days");
        return $this->measuredAt >= $threshold;
    }
}
```

### Technical Notes

- Latency tests should be run client-side (JavaScript WebSocket ping)
- Results submitted to API and stored
- Consider running tests automatically when operator starts a session
- For MVP, can use simple country-to-region estimates as fallback

### Dependencies

- Depends on: F1 (Operator Registration)
- Blocks: F24 (Latency Matching)

-----

# AREA 3: Client & Robot Management

-----

## Feature Spec: F11 - Site/Location Management

**ID**: F11
**Priority**: Must
**Status**: Draft

### Overview

Clients define physical sites/locations where their robots are deployed. Sites are associated with a robot region (for latency matching) and contain one or more robots.

### User Stories

> As a client admin, I want to register my facility locations so that I can organize robots by site.

### Acceptance Criteria

- [ ] Given a client admin, when adding a site, then specify name, address, region
- [ ] Given a site, when adding robots, then robots are associated with that site
- [ ] Given multiple sites, when viewing dashboard, then see coverage status per site
- [ ] Given a site, when selecting region, then choose from predefined robot regions

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'sites')]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClientOrganization::class, inversedBy: 'sites')]
    #[ORM\JoinColumn(nullable: false)]
    private ClientOrganization $organization;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\ManyToOne(targetEntity: RobotRegion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private RobotRegion $region;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $timezoneId = null;

    #[ORM\OneToMany(targetEntity: Robot::class, mappedBy: 'site')]
    private Collection $robots;

    #[ORM\Column(enumType: SiteStatus::class)]
    private SiteStatus $status = SiteStatus::ACTIVE;
}
```

### Dependencies

- Depends on: F2 (Client Registration)
- Blocks: F12 (Robot Registration), F14 (Coverage Requirements)

-----

## Feature Spec: F12 - Robot Registration

**ID**: F12
**Priority**: Must
**Status**: Draft

### Overview

Clients register individual robots specifying model, capabilities, and connection details. Each robot belongs to a site and can be assigned to operators for control.

### User Stories

> As a client admin, I want to register my robots so that I can schedule operator coverage for them.

### Acceptance Criteria

- [ ] Given a client admin, when adding a robot, then specify name, model, site, capabilities
- [ ] Given a robot, when setting connection endpoint, then store WebRTC/streaming endpoint
- [ ] Given a robot, when viewing details, then see current status and assigned operator
- [ ] Given robot registration, when model selected, then auto-populate required skills

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'robots')]
class Robot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'robots')]
    #[ORM\JoinColumn(nullable: false)]
    private Site $site;

    #[ORM\Column(length: 100)]
    private string $name;  // e.g., "Robot-A1", "Shelf-Bot-03"

    #[ORM\Column(length: 100)]
    private string $model;  // e.g., "OpenArm-7DOF", "Agility-A1"

    #[ORM\Column(type: Types::JSON)]
    private array $capabilities = [];  // List of skill IDs required to operate

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $connectionEndpoint = null;  // WebRTC signaling URL

    #[ORM\Column(enumType: RobotStatus::class)]
    private RobotStatus $status = RobotStatus::OFFLINE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;
}

enum RobotStatus: string
{
    case OFFLINE = 'offline';
    case ONLINE = 'online';
    case IN_SESSION = 'in_session';
    case MAINTENANCE = 'maintenance';
    case ERROR = 'error';
}
```

### Dependencies

- Depends on: F11 (Site Management)
- Blocks: F14 (Coverage Requirements), F15 (Job Posting)

-----

## Feature Spec: F14 - Coverage Requirements

**ID**: F14
**Priority**: Must
**Status**: Draft

### Overview

Clients define the hours they need operator coverage for their robots. This creates the demand that the scheduling engine fulfills.

### User Stories

> As a client admin, I want to specify that my robots need coverage 24/7 so that operators are scheduled accordingly.

> As a client admin, I want to specify coverage only during business hours to control costs.

### Acceptance Criteria

- [ ] Given a client admin, when setting coverage for a robot group, then specify hours per day
- [ ] Given coverage requirements, when generating shifts, then create shifts matching required hours
- [ ] Given 24/7 coverage, when timezone boundary crossed, then handle correctly
- [ ] Given partial coverage (e.g., 8am-6pm), when viewing schedule, then show uncovered gaps

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'coverage_requirements')]
class CoverageRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Site $site;

    // Can be specific to a robot or apply to whole site
    #[ORM\ManyToOne(targetEntity: Robot::class)]
    private ?Robot $robot = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $dayOfWeek;  // 0=Sunday

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startTime;  // Site local time

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(type: Types::INTEGER)]
    private int $minOperators = 1;  // How many operators needed

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxLatencyMs = 150;  // Latency requirement

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $effectiveFrom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $effectiveUntil = null;
}
```

### Dependencies

- Depends on: F11 (Sites), F12 (Robots)
- Blocks: F16 (Shift Definition)

-----

*Continue to [Part 2: Job/Shift Management, Matching Engine, Sessions](./03-SPECIFICATIONS-PART2.md)*
