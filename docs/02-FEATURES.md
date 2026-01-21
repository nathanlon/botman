# TeleOps Scheduling Engine - Feature List

## Feature Areas

### Area 1: User & Identity Management

|ID|Feature              |Description                                                              |Priority|Notes                         |
|--|---------------------|-------------------------------------------------------------------------|--------|------------------------------|
|F1|Operator Registration|Operators can create accounts with profile, location, timezone, equipment|Must    |Foundation - blocks everything|
|F2|Client Registration  |Businesses can create accounts, add billing contacts                     |Must    |Foundation                    |
|F3|Authentication       |JWT-based auth with refresh tokens, MFA optional                         |Must    |Security foundation           |
|F4|Role-Based Access    |Operators, Clients, Admins have different permissions                    |Must    |                              |
|F5|Profile Management   |Users can update profiles, contact info, preferences                     |Should  |                              |

### Area 2: Operator Capabilities

|ID |Feature                   |Description                                                       |Priority|Notes                    |
|---|--------------------------|------------------------------------------------------------------|--------|-------------------------|
|F6 |Skill Profiles            |Operators define skills (task types, robot models, certifications)|Must    |Critical for matching    |
|F7 |Certification Tracking    |Track certifications (ExoArm-7, OpenArm, safety certs) with expiry|Must    |Compliance requirement   |
|F8 |Equipment Registration    |Operators register their exoskeleton equipment, verify working    |Should  |Equipment standardization|
|F9 |Availability Management   |Operators set recurring weekly availability + one-off overrides   |Must    |Core scheduling input    |
|F10|Location & Latency Profile|Store operator location, measure/estimate latency to robot regions|Must    |Critical for matching    |

### Area 3: Client & Robot Management

|ID |Feature                 |Description                                                         |Priority|Notes                  |
|---|------------------------|--------------------------------------------------------------------|--------|-----------------------|
|F11|Site/Location Management|Clients define physical sites where robots are deployed             |Must    |Latency matching basis |
|F12|Robot Registration      |Clients register robots with type, capabilities, connection endpoint|Must    |What gets controlled   |
|F13|Robot Groups            |Group robots by site, task type, or custom criteria                 |Should  |Operational convenience|
|F14|Coverage Requirements   |Clients define required coverage hours per robot/group              |Must    |Scheduling target      |

### Area 4: Job & Shift Management

|ID |Feature                    |Description                                                    |Priority|Notes          |
|---|---------------------------|---------------------------------------------------------------|--------|---------------|
|F15|Job Posting                |Clients create jobs specifying requirements, duration, pay rate|Must    |Demand signal  |
|F16|Shift Definition           |Break jobs into schedulable shifts (e.g., 4-hour blocks)       |Must    |Scheduling unit|
|F17|Shift Assignment           |Assign operators to shifts (manual or automated)               |Must    |Core matching  |
|F18|Schedule Conflict Detection|Prevent double-booking operators                               |Must    |Data integrity |
|F19|Shift Swaps                |Operators can request shift swaps, subject to approval         |Should  |Flexibility    |
|F20|Emergency Coverage         |Auto-find replacement when operator can't make shift           |Must    |Reliability    |

### Area 5: Matching Engine

|ID |Feature                 |Description                                           |Priority|Notes                |
|---|------------------------|------------------------------------------------------|--------|---------------------|
|F21|Skill Matching          |Match operator skills to job requirements             |Must    |Core algorithm       |
|F22|Availability Matching   |Only match available operators                        |Must    |Core algorithm       |
|F23|Timezone Optimization   |Prefer operators whose working hours align naturally  |Should  |Quality of life      |
|F24|Latency Matching        |Only match operators within latency threshold of robot|Must    |Technical requirement|
|F25|Load Balancing          |Distribute work fairly among qualified operators      |Should  |Fairness             |
|F26|Priority/Rating Matching|Prefer higher-rated operators for premium clients     |Could   |Tiered service       |

### Area 6: Real-Time Session Management

|ID |Feature               |Description                                             |Priority|Notes                |
|---|----------------------|--------------------------------------------------------|--------|---------------------|
|F27|Session Start/End     |Track when operator begins/ends controlling robot       |Must    |Billing, metrics     |
|F28|Session State Tracking|Track active control vs monitoring vs idle              |Must    |Operational awareness|
|F29|Heartbeat Monitoring  |Detect operator disconnection, trigger alerts           |Must    |Reliability          |
|F30|Shift Handoff         |Coordinate handoff between operators at shift boundaries|Must    |Continuity           |
|F31|Escalation Alerts     |Alert admins when coverage gaps or issues arise         |Must    |Operational          |

### Area 7: Compensation & Metrics

|ID |Feature             |Description                                             |Priority|Notes              |
|---|--------------------|--------------------------------------------------------|--------|-------------------|
|F32|Time Tracking       |Track hours worked per shift, operator, client          |Must    |Billing basis      |
|F33|Rate Management     |Define pay rates by skill level, client tier, shift type|Should  |Flexibility        |
|F34|Compensation Reports|Generate reports for payroll processing                 |Should  |Export, not payment|
|F35|Quality Metrics     |Track intervention rates, task success, client feedback |Must    |Quality assurance  |
|F36|Operator Ratings    |Clients rate operators, aggregate into scores           |Should  |Marketplace trust  |

### Area 8: Administration

|ID |Feature            |Description                                         |Priority|Notes                |
|---|-------------------|----------------------------------------------------|--------|---------------------|
|F37|Admin Dashboard    |Overview of system health, active sessions, coverage|Must    |Operational          |
|F38|Operator Management|Admins can suspend, verify, manage operator accounts|Must    |Platform control     |
|F39|Client Management  |Admins can manage client accounts, contracts        |Must    |Platform control     |
|F40|Audit Logging      |Log all significant actions for compliance          |Must    |Compliance           |
|F41|Reporting          |Generate operational and business reports           |Should  |Business intelligence|

### Area 9: Notifications

|ID |Feature                  |Description                              |Priority|Notes           |
|---|-------------------------|-----------------------------------------|--------|----------------|
|F42|Shift Reminders          |Remind operators of upcoming shifts      |Must    |Reduce no-shows |
|F43|Assignment Notifications |Notify operators of new shift assignments|Must    |Communication   |
|F44|Alert Notifications      |Notify admins of coverage gaps, issues   |Must    |Operational     |
|F45|Email Integration        |Send notifications via email             |Must    |Baseline channel|
|F46|Webhook/API Notifications|Send notifications to external systems   |Could   |Integration     |

-----

## Priority Key

- **Must**: Required for MVP / launch (20 features)
- **Should**: Important but not blocking (12 features)
- **Could**: Nice to have (3 features)
- **Won't**: Explicitly deferred (not listed)

## Feature Count Summary

|Priority |Count |
|---------|------|
|Must     |28    |
|Should   |15    |
|Could    |3     |
|**Total**|**46**|

-----

## MVP Feature Set (Must-haves)

Core features for minimum viable product:

1. **Identity**: F1, F2, F3, F4 (Registration, Auth, Roles)
1. **Operator Setup**: F6, F7, F9, F10 (Skills, Certs, Availability, Location)
1. **Client Setup**: F11, F12, F14 (Sites, Robots, Coverage)
1. **Scheduling**: F15, F16, F17, F18, F20 (Jobs, Shifts, Assignment, Conflicts, Emergency)
1. **Matching**: F21, F22, F24 (Skills, Availability, Latency)
1. **Sessions**: F27, F28, F29, F30, F31 (Start/End, State, Heartbeat, Handoff, Alerts)
1. **Metrics**: F32, F35 (Time Tracking, Quality)
1. **Admin**: F37, F38, F39, F40 (Dashboard, Management, Audit)
1. **Notifications**: F42, F43, F44, F45 (Reminders, Assignments, Alerts, Email)

-----

*Next Step: [Specification Development](./03-SPECIFICATIONS.md)*
