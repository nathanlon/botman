# TeleOps Scheduling Engine - Feature Specifications (Part 2)

# AREA 4: Job & Shift Management

-----

## Feature Spec: F15 - Job Posting

**ID**: F15
**Priority**: Must
**Status**: Draft

### Overview

Clients create jobs specifying the work to be done, required skills, duration, and compensation. Jobs are the top-level container that gets broken into schedulable shifts.

### User Stories

> As a client admin, I want to post a job so that operators can be assigned to control my robots.

> As a client admin, I want to specify skill requirements so that only qualified operators are matched.

### Acceptance Criteria

- [ ] Given a client admin, when creating a job, then specify title, description, site, robots, dates
- [ ] Given job creation, when selecting skills, then choose from predefined skill list
- [ ] Given a job, when setting dates, then specify start date, end date, and daily coverage hours
- [ ] Given a job, when setting pay rate, then specify hourly rate and currency
- [ ] Given a posted job, when it goes live, then operators can view in marketplace
- [ ] Given job requirements, when no operators match, then alert client

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'jobs')]
class Job
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ClientOrganization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ClientOrganization $organization;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Site $site;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::JSON)]
    private array $requiredSkillIds = [];

    #[ORM\Column(type: Types::JSON)]
    private array $requiredCertificationTypeIds = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $maxLatencyMs = 150;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $hourlyRateAmount;

    #[ORM\Column(length: 3)]
    private string $hourlyRateCurrency = 'USD';

    #[ORM\Column(enumType: JobStatus::class)]
    private JobStatus $status = JobStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: ClientUser::class)]
    private ?ClientUser $createdBy = null;

    #[ORM\OneToMany(targetEntity: Shift::class, mappedBy: 'job')]
    private Collection $shifts;

    #[ORM\ManyToMany(targetEntity: Robot::class)]
    #[ORM\JoinTable(name: 'job_robots')]
    private Collection $robots;
}

enum JobStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';           // Accepting operator assignments
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
```

### Dependencies

- Depends on: F2 (Client), F11 (Sites), F12 (Robots), F6 (Skills)
- Blocks: F16 (Shifts), F21 (Matching)

-----

## Feature Spec: F16 - Shift Definition

**ID**: F16
**Priority**: Must
**Status**: Draft

### Overview

Jobs are broken into individual shifts - the atomic unit of scheduled work. Each shift represents a specific time period during which an operator is needed to control robot(s).

### User Stories

> As the system, I want to automatically generate shifts from job requirements so that operators can be assigned specific time slots.

> As a client admin, I want to manually create shifts for ad-hoc coverage needs.

### Acceptance Criteria

- [ ] Given a job with coverage hours, when activated, then generate shifts for each time block
- [ ] Given shift generation, when daily coverage is 24 hours, then create appropriate shift blocks (e.g., 3x8hr or 4x6hr)
- [ ] Given shifts, when viewing schedule, then show as calendar entries
- [ ] Given a shift, when no operator assigned, then show as "unassigned"
- [ ] Given manual shift creation, when specifying time, then validate against job boundaries

### Business Rules

1. **Shift duration**: Default 4-hour shifts, configurable 2-8 hours
1. **Shift overlap**: Small overlap (15 min) for handoff
1. **Minimum notice**: Shifts must be created at least 24 hours before start
1. **Shift granularity**: Start/end times rounded to 15-minute increments

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'shifts')]
#[ORM\Index(columns: ['start_time_utc', 'end_time_utc'], name: 'idx_shift_times')]
#[ORM\Index(columns: ['status'], name: 'idx_shift_status')]
class Shift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Job::class, inversedBy: 'shifts')]
    #[ORM\JoinColumn(nullable: false)]
    private Job $job;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startTimeUtc;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endTimeUtc;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    private ?Operator $assignedOperator = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $assignedAt = null;

    #[ORM\Column(enumType: ShiftStatus::class)]
    private ShiftStatus $status = ShiftStatus::UNASSIGNED;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'shift')]
    private Collection $sessions;

    public function getDurationMinutes(): int
    {
        return (int) (($this->endTimeUtc->getTimestamp() - $this->startTimeUtc->getTimestamp()) / 60);
    }
}

enum ShiftStatus: string
{
    case UNASSIGNED = 'unassigned';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case MISSED = 'missed';        // Operator didn't show
    case CANCELLED = 'cancelled';
}
```

### Service Layer

```php
class ShiftGenerationService
{
    public function generateShiftsForJob(Job $job): array
    {
        $shifts = [];
        $currentDate = $job->getStartDate();
        $endDate = $job->getEndDate();

        while ($currentDate <= $endDate) {
            // Get coverage requirements for this day
            $coverageHours = $this->getCoverageForDay($job, $currentDate);

            // Split into shifts (default 4 hours)
            foreach ($coverageHours as $coverageBlock) {
                $shiftDuration = 4 * 60; // 4 hours in minutes
                $blockStart = $coverageBlock['start'];
                $blockEnd = $coverageBlock['end'];

                while ($blockStart < $blockEnd) {
                    $shiftEnd = min(
                        $blockStart->modify("+{$shiftDuration} minutes"),
                        $blockEnd
                    );

                    $shift = new Shift();
                    $shift->setJob($job);
                    $shift->setStartTimeUtc($this->toUtc($blockStart, $job->getSite()));
                    $shift->setEndTimeUtc($this->toUtc($shiftEnd, $job->getSite()));

                    $shifts[] = $shift;
                    $blockStart = $shiftEnd;
                }
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $shifts;
    }
}
```

### Dependencies

- Depends on: F15 (Jobs), F14 (Coverage Requirements)
- Blocks: F17 (Assignment), F18 (Conflict Detection)

-----

## Feature Spec: F17 - Shift Assignment

**ID**: F17
**Priority**: Must
**Status**: Draft

### Overview

Assign operators to shifts, either automatically through the matching engine or manually by admins. This is the core scheduling action.

### User Stories

> As the system, I want to automatically assign qualified operators to shifts so that coverage is achieved with minimal manual intervention.

> As an operator, I want to accept available shifts so that I can earn income.

> As a client admin, I want to manually assign specific operators to shifts when needed.

### Acceptance Criteria

- [ ] Given an unassigned shift, when an operator accepts it, then operator is assigned and shift status changes
- [ ] Given shift acceptance, when operator already has overlapping shift, then reject with conflict error
- [ ] Given auto-assignment enabled, when shift is created, then automatically find and assign best match
- [ ] Given an assigned shift, when operator is unavailable, then trigger emergency coverage process
- [ ] Given a shift, when reassignment needed, then allow admin to manually reassign

### Business Rules

1. **First-come-first-served**: For open shifts, first qualified operator to accept gets it
1. **Conflict prevention**: Cannot assign operator to overlapping shifts
1. **Minimum notice for acceptance**: Operators must accept at least 2 hours before shift start
1. **Cancellation penalty**: Operators who cancel within 24 hours may face rating impact

### Service Layer

```php
class ShiftAssignmentService
{
    public function __construct(
        private readonly MatchingService $matchingService,
        private readonly AvailabilityService $availabilityService,
        private readonly EntityManagerInterface $em,
        private readonly NotificationService $notifications,
    ) {}

    public function assignOperatorToShift(
        Operator $operator,
        Shift $shift,
        bool $bypassChecks = false
    ): AssignmentResult {
        // 1. Verify shift is assignable
        if ($shift->getStatus() !== ShiftStatus::UNASSIGNED) {
            return AssignmentResult::fail('Shift is not available');
        }

        // 2. Check operator qualifications (unless bypassed by admin)
        if (!$bypassChecks) {
            $matchResult = $this->matchingService->checkOperatorMatchesJob(
                $operator,
                $shift->getJob()
            );
            if (!$matchResult->isMatch()) {
                return AssignmentResult::fail($matchResult->getReason());
            }
        }

        // 3. Check for conflicts
        if ($this->hasConflictingShift($operator, $shift)) {
            return AssignmentResult::fail('Operator has conflicting shift');
        }

        // 4. Check availability
        if (!$this->availabilityService->isOperatorAvailable(
            $operator,
            $shift->getStartTimeUtc(),
            $shift->getEndTimeUtc()
        )) {
            return AssignmentResult::fail('Operator not available during shift');
        }

        // 5. Perform assignment
        $shift->setAssignedOperator($operator);
        $shift->setAssignedAt(new \DateTimeImmutable());
        $shift->setStatus(ShiftStatus::ASSIGNED);

        $this->em->flush();

        // 6. Send notifications
        $this->notifications->notifyShiftAssigned($shift);

        return AssignmentResult::success();
    }

    private function hasConflictingShift(Operator $operator, Shift $newShift): bool
    {
        $qb = $this->em->createQueryBuilder();
        $conflicts = $qb->select('COUNT(s)')
            ->from(Shift::class, 's')
            ->where('s.assignedOperator = :operator')
            ->andWhere('s.status IN (:activeStatuses)')
            ->andWhere('s.startTimeUtc < :newEnd')
            ->andWhere('s.endTimeUtc > :newStart')
            ->setParameter('operator', $operator)
            ->setParameter('activeStatuses', [ShiftStatus::ASSIGNED, ShiftStatus::IN_PROGRESS])
            ->setParameter('newStart', $newShift->getStartTimeUtc())
            ->setParameter('newEnd', $newShift->getEndTimeUtc())
            ->getQuery()
            ->getSingleScalarResult();

        return $conflicts > 0;
    }

    public function autoAssignShift(Shift $shift): AssignmentResult
    {
        // Find best matching operator
        $candidates = $this->matchingService->findMatchingOperators($shift->getJob());

        foreach ($candidates as $candidate) {
            $result = $this->assignOperatorToShift($candidate, $shift);
            if ($result->isSuccess()) {
                return $result;
            }
        }

        return AssignmentResult::fail('No matching operators available');
    }
}
```

### Dependencies

- Depends on: F16 (Shifts), F21-24 (Matching), F9 (Availability)
- Blocks: F20 (Emergency Coverage), F27 (Sessions)

-----

## Feature Spec: F18 - Schedule Conflict Detection

**ID**: F18
**Priority**: Must
**Status**: Draft

### Overview

Prevent operators from being double-booked by detecting and rejecting conflicting shift assignments.

### Acceptance Criteria

- [ ] Given an operator with an assigned shift, when assigning to overlapping shift, then reject
- [ ] Given overlap detection, when shifts touch but don't overlap, then allow (e.g., 8:00-12:00 and 12:00-16:00)
- [ ] Given conflict check, when comparing times, then use UTC for consistency
- [ ] Given bulk assignment, when any conflict exists, then reject entire batch

### Technical Notes

- Implement as database constraint + application-level check
- Use pessimistic locking during assignment to prevent race conditions

```php
// Repository method for conflict detection
class ShiftRepository extends ServiceEntityRepository
{
    public function findConflictingShifts(
        Operator $operator,
        \DateTimeImmutable $startUtc,
        \DateTimeImmutable $endUtc,
        ?int $excludeShiftId = null
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.assignedOperator = :operator')
            ->andWhere('s.status NOT IN (:excludedStatuses)')
            ->andWhere('s.startTimeUtc < :end')
            ->andWhere('s.endTimeUtc > :start')
            ->setParameter('operator', $operator)
            ->setParameter('excludedStatuses', [ShiftStatus::CANCELLED, ShiftStatus::COMPLETED])
            ->setParameter('start', $startUtc)
            ->setParameter('end', $endUtc);

        if ($excludeShiftId) {
            $qb->andWhere('s.id != :excludeId')
               ->setParameter('excludeId', $excludeShiftId);
        }

        return $qb->getQuery()->getResult();
    }
}
```

### Dependencies

- Depends on: F16 (Shifts), F17 (Assignment)
- Blocks: Nothing directly (validation feature)

-----

## Feature Spec: F20 - Emergency Coverage

**ID**: F20
**Priority**: Must
**Status**: Draft

### Overview

When an assigned operator cannot make their shift, automatically find a replacement to maintain coverage.

### User Stories

> As an operator, I want to cancel a shift when I'm unable to work so that a replacement can be found.

> As the system, I want to automatically find replacement operators when a shift becomes uncovered.

### Acceptance Criteria

- [ ] Given an assigned operator, when they cancel a shift, then shift becomes unassigned
- [ ] Given a cancelled shift, when within 24 hours of start, then trigger emergency coverage
- [ ] Given emergency coverage, when searching for replacements, then prioritize by availability and rating
- [ ] Given no replacement found, when shift is imminent, then alert admins
- [ ] Given emergency coverage, when replacement accepts, then notify client

### Business Rules

1. **Escalation timeline**:
- 24 hours: Normal reassignment queue
- 4-24 hours: Emergency queue, bonus pay incentive
- <4 hours: Admin alert, client notified of potential gap
1. **Bonus pay**: 1.5x rate for emergency coverage

### Service Layer

```php
class EmergencyCoverageService
{
    public function handleShiftCancellation(Shift $shift, string $reason): void
    {
        $hoursUntilShift = $this->getHoursUntilShift($shift);

        // Mark shift as needing coverage
        $shift->setAssignedOperator(null);
        $shift->setStatus(ShiftStatus::UNASSIGNED);

        // Determine urgency
        $urgency = match(true) {
            $hoursUntilShift < 4 => CoverageUrgency::CRITICAL,
            $hoursUntilShift < 24 => CoverageUrgency::EMERGENCY,
            default => CoverageUrgency::NORMAL,
        };

        // Create coverage request
        $request = new CoverageRequest();
        $request->setShift($shift);
        $request->setUrgency($urgency);
        $request->setReason($reason);
        $request->setBonusMultiplier($urgency === CoverageUrgency::EMERGENCY ? 1.5 : 1.0);

        $this->em->persist($request);
        $this->em->flush();

        // Dispatch to message queue
        $this->messageBus->dispatch(new FindReplacementOperatorMessage($request->getId()));

        // Critical shifts get immediate admin alert
        if ($urgency === CoverageUrgency::CRITICAL) {
            $this->notificationService->alertAdminsOfCriticalCoverage($request);
        }
    }
}
```

### Dependencies

- Depends on: F17 (Assignment), F21-24 (Matching)
- Blocks: F31 (Escalation Alerts)

-----

# AREA 5: Matching Engine

-----

## Feature Spec: F21 - Skill Matching

**ID**: F21
**Priority**: Must
**Status**: Draft

### Overview

Match operators to jobs based on required skills. Operator must possess ALL skills required by the job.

### Acceptance Criteria

- [ ] Given a job requiring skills A, B, C, when matching, then only include operators with all three
- [ ] Given skill matching, when considering proficiency, then require minimum proficiency level
- [ ] Given an operator missing one skill, when matching, then exclude them
- [ ] Given skill search, when many operators match, then return all matches (sorting separate)

### Service Layer

```php
class SkillMatchingService
{
    public function operatorMeetsSkillRequirements(
        Operator $operator,
        array $requiredSkillIds,
        int $minProficiency = 1
    ): MatchResult {
        $operatorSkills = $this->operatorSkillRepository->findByOperator($operator);
        $operatorSkillMap = [];

        foreach ($operatorSkills as $os) {
            $operatorSkillMap[$os->getSkill()->getId()] = $os->getProficiencyLevel();
        }

        $missingSkills = [];
        $insufficientProficiency = [];

        foreach ($requiredSkillIds as $skillId) {
            if (!isset($operatorSkillMap[$skillId])) {
                $missingSkills[] = $skillId;
            } elseif ($operatorSkillMap[$skillId] < $minProficiency) {
                $insufficientProficiency[] = $skillId;
            }
        }

        if (empty($missingSkills) && empty($insufficientProficiency)) {
            return MatchResult::success();
        }

        return MatchResult::fail(
            'Missing skills: ' . implode(',', $missingSkills) .
            ' Insufficient proficiency: ' . implode(',', $insufficientProficiency)
        );
    }
}
```

### Dependencies

- Depends on: F6 (Skill Profiles)
- Blocks: F17 (Assignment)

-----

## Feature Spec: F22 - Availability Matching

**ID**: F22
**Priority**: Must
**Status**: Draft

### Overview

Only match operators to shifts during their available hours.

### Acceptance Criteria

- [ ] Given a shift time, when matching operators, then only include those available for entire shift
- [ ] Given operator availability in local time, when matching UTC shift, then convert correctly
- [ ] Given availability override, when present for shift date, then use override instead of weekly schedule

### Technical Notes

```php
class AvailabilityMatchingService
{
    public function getAvailableOperators(
        \DateTimeImmutable $startUtc,
        \DateTimeImmutable $endUtc
    ): array {
        // 1. Get all active operators
        $operators = $this->operatorRepository->findActive();

        // 2. Filter by availability
        $available = [];
        foreach ($operators as $operator) {
            if ($this->availabilityService->isOperatorAvailable($operator, $startUtc, $endUtc)) {
                $available[] = $operator;
            }
        }

        return $available;
    }
}
```

### Dependencies

- Depends on: F9 (Availability Management)
- Blocks: F17 (Assignment)

-----

## Feature Spec: F24 - Latency Matching

**ID**: F24
**Priority**: Must
**Status**: Draft

### Overview

Only match operators whose network latency to the robot's region is within acceptable thresholds.

### Acceptance Criteria

- [ ] Given a job requiring <150ms latency, when matching, then exclude operators with higher latency
- [ ] Given no latency data for operator, when matching, then exclude with warning
- [ ] Given stale latency data (>7 days), when matching, then flag for re-measurement
- [ ] Given latency measurement, when considering, then use the most recent measurement

### Technical Notes

```php
class LatencyMatchingService
{
    public function operatorMeetsLatencyRequirement(
        Operator $operator,
        RobotRegion $region,
        int $maxLatencyMs
    ): MatchResult {
        $measurement = $this->latencyRepository->findLatestForOperatorAndRegion(
            $operator,
            $region
        );

        if (!$measurement) {
            return MatchResult::fail('No latency data available. Please run speed test.');
        }

        if (!$measurement->isFresh()) {
            // Still allow match but flag for update
            $this->flagForLatencyUpdate($operator, $region);
        }

        if ($measurement->getLatencyMs() > $maxLatencyMs) {
            return MatchResult::fail(
                "Latency {$measurement->getLatencyMs()}ms exceeds maximum {$maxLatencyMs}ms"
            );
        }

        return MatchResult::success();
    }
}
```

### Dependencies

- Depends on: F10 (Location & Latency Profile)
- Blocks: F17 (Assignment)

-----

## Composite: Matching Service

The complete matching service composes all matching criteria:

```php
class MatchingService
{
    public function __construct(
        private readonly SkillMatchingService $skillMatcher,
        private readonly AvailabilityMatchingService $availabilityMatcher,
        private readonly LatencyMatchingService $latencyMatcher,
        private readonly CertificationService $certificationService,
        private readonly OperatorRepository $operatorRepository,
    ) {}

    public function findMatchingOperators(Job $job): array
    {
        // Get all active operators
        $operators = $this->operatorRepository->findActive();

        $matches = [];
        foreach ($operators as $operator) {
            $result = $this->checkOperatorMatchesJob($operator, $job);
            if ($result->isMatch()) {
                $matches[] = [
                    'operator' => $operator,
                    'score' => $result->getScore(),
                ];
            }
        }

        // Sort by score descending
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_column($matches, 'operator');
    }

    public function checkOperatorMatchesJob(Operator $operator, Job $job): CompositeMatchResult
    {
        $results = [];
        $score = 100;

        // 1. Skill matching (required)
        $skillResult = $this->skillMatcher->operatorMeetsSkillRequirements(
            $operator,
            $job->getRequiredSkillIds()
        );
        if (!$skillResult->isSuccess()) {
            return CompositeMatchResult::fail('Skills: ' . $skillResult->getReason());
        }
        $results['skills'] = $skillResult;

        // 2. Certification matching (required)
        $certResult = $this->certificationService->operatorMeetsCertificationRequirements(
            $operator,
            $job->getRequiredCertificationTypeIds()
        );
        if (!$certResult->isSuccess()) {
            return CompositeMatchResult::fail('Certifications: ' . $certResult->getReason());
        }
        $results['certifications'] = $certResult;

        // 3. Latency matching (required)
        $latencyResult = $this->latencyMatcher->operatorMeetsLatencyRequirement(
            $operator,
            $job->getSite()->getRegion(),
            $job->getMaxLatencyMs()
        );
        if (!$latencyResult->isSuccess()) {
            return CompositeMatchResult::fail('Latency: ' . $latencyResult->getReason());
        }
        $results['latency'] = $latencyResult;

        // 4. Calculate match score (for sorting)
        // Higher proficiency = higher score
        // Lower latency = higher score
        // Higher rating = higher score

        return CompositeMatchResult::success($score, $results);
    }
}
```

-----

# AREA 6: Real-Time Session Management

-----

## Feature Spec: F27 - Session Start/End

**ID**: F27
**Priority**: Must
**Status**: Draft

### Overview

Track when operators begin and end controlling robots. Sessions are the billing and metrics unit.

### User Stories

> As an operator, I want to check in when I start my shift so that my work is recorded.

> As the system, I want to track session times accurately for billing and metrics.

### Acceptance Criteria

- [ ] Given an assigned shift, when operator clicks "Start Shift", then create new session
- [ ] Given session start, when verifying, then confirm shift is theirs and time is correct
- [ ] Given an active session, when operator clicks "End Shift", then close session with end time
- [ ] Given session, when calculating duration, then use actual start/end times
- [ ] Given early start (>15 min before shift), when starting, then warn but allow

### Data Requirements

```php
#[ORM\Entity]
#[ORM\Table(name: 'sessions')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shift::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private Shift $shift;

    #[ORM\ManyToOne(targetEntity: Operator::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Operator $operator;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(enumType: SessionStatus::class)]
    private SessionStatus $status = SessionStatus::ACTIVE;

    #[ORM\Column(enumType: SessionEndReason::class, nullable: true)]
    private ?SessionEndReason $endReason = null;

    // Metrics
    #[ORM\Column(type: Types::INTEGER)]
    private int $activeControlSeconds = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $monitoringSeconds = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $interventionCount = 0;

    public function getDurationSeconds(): ?int
    {
        if (!$this->endedAt) {
            return null;
        }
        return $this->endedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }
}

enum SessionStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case DISCONNECTED = 'disconnected';
    case ABORTED = 'aborted';
}

enum SessionEndReason: string
{
    case SHIFT_COMPLETED = 'shift_completed';
    case OPERATOR_ENDED = 'operator_ended';
    case HANDOFF = 'handoff';
    case DISCONNECTION = 'disconnection';
    case ADMIN_TERMINATED = 'admin_terminated';
    case EMERGENCY = 'emergency';
}
```

### Dependencies

- Depends on: F17 (Assignment)
- Blocks: F28 (State Tracking), F32 (Time Tracking)

-----

## Feature Spec: F28 - Session State Tracking

**ID**: F28
**Priority**: Must
**Status**: Draft

### Overview

Track whether operator is actively controlling robot, passively monitoring, or idle within a session.

### Acceptance Criteria

- [ ] Given an active session, when operator takes control of robot, then state becomes "active_control"
- [ ] Given active control, when operator releases control, then state becomes "monitoring"
- [ ] Given monitoring, when no activity for 5 minutes, then state becomes "idle"
- [ ] Given state changes, when persisting, then increment appropriate duration counter

### Technical Notes

- State tracking via WebSocket heartbeats
- Client sends activity type with each heartbeat
- Server aggregates time in each state

### Dependencies

- Depends on: F27 (Session Start/End)
- Blocks: F29 (Heartbeat Monitoring)

-----

## Feature Spec: F29 - Heartbeat Monitoring

**ID**: F29
**Priority**: Must
**Status**: Draft

### Overview

Detect operator disconnection through heartbeat mechanism and trigger alerts/fallback.

### Acceptance Criteria

- [ ] Given an active session, when heartbeat received, then update last_seen timestamp
- [ ] Given no heartbeat for 30 seconds, when checking, then mark session as potentially disconnected
- [ ] Given no heartbeat for 60 seconds, when checking, then trigger disconnect alert
- [ ] Given disconnect, when operator reconnects within 2 minutes, then resume session
- [ ] Given disconnect, when operator doesn't reconnect, then close session and trigger coverage

### Technical Notes

```php
// Heartbeat handler - called by WebSocket server
class HeartbeatService
{
    public function processHeartbeat(
        Session $session,
        string $activityType,
        array $telemetry
    ): void {
        $session->setLastHeartbeatAt(new \DateTimeImmutable());
        $session->updateActivityMetrics($activityType, $telemetry);

        // Store in Redis for real-time monitoring
        $this->redis->setex(
            "session:{$session->getId()}:heartbeat",
            60,
            json_encode([
                'timestamp' => time(),
                'activity' => $activityType,
                'operator_id' => $session->getOperator()->getId(),
            ])
        );
    }
}

// Console command for disconnect detection
#[AsCommand(name: 'app:monitor-heartbeats')]
class HeartbeatMonitorCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (true) {
            $staleThreshold = new \DateTimeImmutable('-30 seconds');
            $disconnectThreshold = new \DateTimeImmutable('-60 seconds');

            $staleSessions = $this->sessionRepository->findStale($staleThreshold);
            foreach ($staleSessions as $session) {
                if ($session->getLastHeartbeatAt() < $disconnectThreshold) {
                    $this->sessionService->handleDisconnection($session);
                } else {
                    $this->alertService->warnPotentialDisconnect($session);
                }
            }

            sleep(5);  // Check every 5 seconds
        }
    }
}
```

### Dependencies

- Depends on: F28 (State Tracking)
- Blocks: F31 (Escalation)

-----

## Feature Spec: F30 - Shift Handoff

**ID**: F30
**Priority**: Must
**Status**: Draft

### Overview

Coordinate smooth transition of robot control from one operator to the next at shift boundaries.

### Acceptance Criteria

- [ ] Given shift ending in 15 minutes, when next shift has assigned operator, then notify both
- [ ] Given handoff time, when both operators ready, then transfer control smoothly
- [ ] Given handoff, when outgoing operator confirms, then end their session
- [ ] Given handoff, when incoming operator confirms, then start their session
- [ ] Given handoff failure, when incoming operator not ready, then extend outgoing shift by 15 min

### Technical Notes

- 15-minute handoff window
- WebSocket coordination between operators
- Session state machine handles transition

### Dependencies

- Depends on: F27 (Sessions), F16 (Shifts)
- Blocks: Nothing directly (integration feature)

-----

*Continue to [Dependency Analysis](./04-DEPENDENCIES.md)*
