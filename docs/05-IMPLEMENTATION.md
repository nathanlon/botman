# TeleOps Scheduling Engine - Implementation Plan

## Summary

|Metric               |Value                   |
|---------------------|------------------------|
|**Total Features**   |28 MVP features         |
|**Total Tasks**      |~95 tasks               |
|**Estimated Effort** |8 weeks (solo developer)|
|**Target Completion**|Week 8                  |

-----

## Technology Stack

|Component|Technology            |Notes                       |
|---------|----------------------|----------------------------|
|Framework|Symfony 7.x           |PHP 8.3+                    |
|Database |mySQL            |Complex queries, time-series|
|Cache    |Redis 7               |Sessions, real-time state   |
|Queue    |Symfony Messenger + Redis|Async processing            |
|WebSocket|Mercure               |Native Symfony integration  |
|Auth     |LexikJWT              |API token auth              |
|Email    |Symfony Mailer        |Start SMTP, upgrade later   |
|API Docs |NelmioApiDoc          |OpenAPI spec generation     |

-----

## Milestones

### Milestone 1: Foundation - Week 1-2

Foundation infrastructure and user registration.

|Task ID|Feature|Task                                                   |Estimate|Depends On        |
|-------|-------|-------------------------------------------------------|--------|------------------|
|T1.1   |Setup  |Initialize Symfony project, configure Docker           |4h      |-                 |
|T1.2   |Setup  |Configure mySQL, Redis connections                |2h      |T1.1              |
|T1.3   |Setup  |Set up Mercure for WebSockets                          |3h      |T1.1              |
|T1.4   |F1     |Create Operator entity and migration                   |2h      |T1.2              |
|T1.5   |F1     |Create OperatorRepository with base queries            |1h      |T1.4              |
|T1.6   |F1     |Create RegistrationService (hash password, create user)|2h      |T1.5              |
|T1.7   |F1     |Create EmailVerificationService                        |2h      |T1.6              |
|T1.8   |F1     |Create OperatorController (register endpoint)          |2h      |T1.6, T1.7        |
|T1.9   |F1     |Add validation constraints to Operator                 |1h      |T1.4              |
|T1.10  |F1     |Write unit tests for RegistrationService               |2h      |T1.6              |
|T1.11  |F2     |Create ClientOrganization, ClientUser entities         |2h      |T1.2              |
|T1.12  |F2     |Create client registration service and controller      |3h      |T1.11             |
|T1.13  |F3     |Install and configure LexikJWT                         |2h      |T1.1              |
|T1.14  |F3     |Create custom UserProvider (multi-type)                |3h      |T1.4, T1.11, T1.13|
|T1.15  |F3     |Create AuthController (login, refresh, logout)         |2h      |T1.14             |
|T1.16  |F3     |Implement refresh token storage and rotation           |2h      |T1.15             |
|T1.17  |F3     |Write auth integration tests                           |3h      |T1.15             |
|T1.18  |F4     |Define security roles in security.yaml                 |1h      |T1.13             |
|T1.19  |F4     |Create base Voter classes for resource access          |2h      |T1.18             |
|T1.20  |F45    |Configure Symfony Mailer with SMTP                     |1h      |T1.1              |
|T1.21  |F45    |Create email templates (verification, welcome)         |2h      |T1.20             |
|T1.22  |F40    |Create AuditLog entity and service                     |2h      |T1.2              |
|T1.23  |F40    |Create event subscriber for audit events               |2h      |T1.22             |

**Milestone Deliverable**: Users can register (operator or client) and log in via JWT

**Total Estimate**: ~44 hours (Week 1-2)

-----

### Milestone 2: Profiles & Setup - Week 2-3

Operator skills, availability, client sites and robots.

|Task ID|Feature|Task                                                    |Estimate|Depends On |
|-------|-------|--------------------------------------------------------|--------|-----------|
|T2.1   |F6     |Create SkillDefinition entity (seeded data)             |2h      |M1         |
|T2.2   |F6     |Create OperatorSkill entity                             |1h      |T2.1       |
|T2.3   |F6     |Create SkillProfileService                              |2h      |T2.2       |
|T2.4   |F6     |Create skill management endpoints                       |2h      |T2.3       |
|T2.5   |F7     |Create CertificationType, OperatorCertification entities|2h      |M1         |
|T2.6   |F7     |Create CertificationService (add, verify, check expiry) |2h      |T2.5       |
|T2.7   |F7     |Create certification management endpoints               |2h      |T2.6       |
|T2.8   |F7     |Add console command for expiry notifications            |2h      |T2.6       |
|T2.9   |F9     |Create OperatorWeeklyAvailability entity                |2h      |M1         |
|T2.10  |F9     |Create OperatorAvailabilityOverride entity              |1h      |T2.9       |
|T2.11  |F9     |Create AvailabilityService (set, query, compute)        |4h      |T2.9, T2.10|
|T2.12  |F9     |Implement timezone conversion logic                     |3h      |T2.11      |
|T2.13  |F9     |Create availability management endpoints                |2h      |T2.11      |
|T2.14  |F9     |Write availability calculation tests (DST edge cases)   |3h      |T2.12      |
|T2.15  |F10    |Create RobotRegion entity (seeded data)                 |1h      |M1         |
|T2.16  |F10    |Create OperatorLatencyMeasurement entity                |1h      |T2.15      |
|T2.17  |F10    |Create LatencyService (store, query freshness)          |2h      |T2.16      |
|T2.18  |F10    |Create latency submission endpoint                      |1h      |T2.17      |
|T2.19  |F11    |Create Site entity                                      |1h      |M1         |
|T2.20  |F11    |Create SiteService and repository                       |2h      |T2.19      |
|T2.21  |F11    |Create site management endpoints                        |2h      |T2.20      |
|T2.22  |F11    |Add SiteVoter for org-level access control              |1h      |T2.21      |
|T2.23  |F12    |Create Robot entity                                     |2h      |T2.19      |
|T2.24  |F12    |Create RobotService and repository                      |2h      |T2.23      |
|T2.25  |F12    |Create robot management endpoints                       |2h      |T2.24      |
|T2.26  |F12    |Add RobotVoter for access control                       |1h      |T2.25      |

**Milestone Deliverable**: Full profile management for operators (skills, certs, availability) and clients (sites, robots)

**Total Estimate**: ~48 hours (Week 2-3)

-----

### Milestone 3: Matching Engine & Jobs - Week 3-4

Core matching algorithms and job posting.

|Task ID|Feature|Task                                              |Estimate|Depends On      |
|-------|-------|--------------------------------------------------|--------|----------------|
|T3.1   |F21    |Create SkillMatchingService                       |3h      |M2              |
|T3.2   |F21    |Add skill matching query methods to repository    |2h      |T3.1            |
|T3.3   |F21    |Write unit tests for skill matching               |2h      |T3.1            |
|T3.4   |F22    |Create AvailabilityMatchingService                |3h      |M2              |
|T3.5   |F22    |Optimize availability query with Redis cache      |3h      |T3.4            |
|T3.6   |F22    |Write unit tests for availability matching        |2h      |T3.4            |
|T3.7   |F24    |Create LatencyMatchingService                     |2h      |M2              |
|T3.8   |F24    |Write unit tests for latency matching             |1h      |T3.7            |
|T3.9   |-      |Create composite MatchingService                  |3h      |T3.1, T3.4, T3.7|
|T3.10  |-      |Write integration tests for matching              |3h      |T3.9            |
|T3.11  |F14    |Create CoverageRequirement entity                 |2h      |M2              |
|T3.12  |F14    |Create CoverageService                            |2h      |T3.11           |
|T3.13  |F14    |Create coverage management endpoints              |2h      |T3.12           |
|T3.14  |F15    |Create Job entity                                 |2h      |M2              |
|T3.15  |F15    |Create JobService (create, update, status changes)|3h      |T3.14           |
|T3.16  |F15    |Create job management endpoints                   |2h      |T3.15           |
|T3.17  |F15    |Create job marketplace endpoint (for operators)   |2h      |T3.15, T3.9     |
|T3.18  |F15    |Add JobVoter for access control                   |1h      |T3.16           |

**Milestone Deliverable**: Jobs can be posted with requirements, matching engine finds qualified operators

**Total Estimate**: ~40 hours (Week 3-4)

-----

### Milestone 4: Scheduling Core - Week 4-5

Shift generation, assignment, and conflict detection.

|Task ID|Feature|Task                                            |Estimate|Depends On      |
|-------|-------|------------------------------------------------|--------|----------------|
|T4.1   |F16    |Create Shift entity                             |2h      |M3              |
|T4.2   |F16    |Create ShiftGenerationService                   |4h      |T4.1, T3.11     |
|T4.3   |F16    |Add shift generation on job activation          |2h      |T4.2            |
|T4.4   |F16    |Create shift view endpoints                     |2h      |T4.1            |
|T4.5   |F16    |Write tests for shift generation                |3h      |T4.2            |
|T4.6   |F18    |Add conflict detection to ShiftRepository       |2h      |T4.1            |
|T4.7   |F18    |Write conflict detection tests                  |2h      |T4.6            |
|T4.8   |F17    |Create AssignmentResult value object            |1h      |T4.1            |
|T4.9   |F17    |Create ShiftAssignmentService                   |4h      |T4.8, T4.6, T3.9|
|T4.10  |F17    |Add pessimistic locking for assignments         |2h      |T4.9            |
|T4.11  |F17    |Create shift acceptance endpoint (for operators)|2h      |T4.9            |
|T4.12  |F17    |Create manual assignment endpoint (for admins)  |2h      |T4.9            |
|T4.13  |F17    |Create auto-assignment console command          |3h      |T4.9            |
|T4.14  |F17    |Write assignment integration tests              |4h      |T4.9            |
|T4.15  |F42    |Create ShiftReminderService                     |2h      |T4.1            |
|T4.16  |F42    |Add reminder console command (scheduled)        |2h      |T4.15           |
|T4.17  |F43    |Create assignment notification events           |2h      |T4.9            |
|T4.18  |F43    |Create notification event subscriber            |2h      |T4.17           |

**Milestone Deliverable**: Full shift scheduling - generation, assignment, conflict detection, notifications

**Total Estimate**: ~43 hours (Week 4-5)

-----

### Milestone 5: Real-Time Sessions - Week 5-7

Live session management, heartbeats, handoffs.

|Task ID|Feature|Task                                         |Estimate|Depends On |
|-------|-------|---------------------------------------------|--------|-----------|
|T5.1   |F27    |Create Session entity                        |2h      |M4         |
|T5.2   |F27    |Create SessionService (start, end, query)    |3h      |T5.1       |
|T5.3   |F27    |Create session start/end endpoints           |2h      |T5.2       |
|T5.4   |F27    |Add shift status updates on session events   |2h      |T5.2       |
|T5.5   |F27    |Write session lifecycle tests                |2h      |T5.2       |
|T5.6   |F28    |Add state tracking to Session entity         |1h      |T5.1       |
|T5.7   |F28    |Create activity state machine                |3h      |T5.6       |
|T5.8   |F28    |Create state update endpoint                 |1h      |T5.7       |
|T5.9   |F29    |Create HeartbeatService                      |2h      |T5.6       |
|T5.10  |F29    |Set up Redis for heartbeat storage           |1h      |T5.9       |
|T5.11  |F29    |Create WebSocket heartbeat endpoint (Mercure)|3h      |T5.9, T5.10|
|T5.12  |F29    |Create heartbeat monitor console command     |3h      |T5.9       |
|T5.13  |F29    |Add disconnect handling logic                |3h      |T5.12      |
|T5.14  |F29    |Write heartbeat monitoring tests             |2h      |T5.12      |
|T5.15  |F30    |Create HandoffService                        |4h      |T5.2       |
|T5.16  |F30    |Add handoff coordination via Mercure         |3h      |T5.15      |
|T5.17  |F30    |Create handoff initiation console command    |2h      |T5.15      |
|T5.18  |F30    |Write handoff scenario tests                 |3h      |T5.15      |
|T5.19  |F32    |Create TimeTrackingService                   |2h      |T5.2       |
|T5.20  |F32    |Add time aggregation queries                 |2h      |T5.19      |
|T5.21  |F32    |Create time tracking report endpoint         |2h      |T5.20      |

**Milestone Deliverable**: Full real-time session management with heartbeat monitoring and handoffs

**Total Estimate**: ~48 hours (Week 5-7)

-----

### Milestone 6: Admin & Metrics - Week 7-8

Emergency coverage, alerts, dashboard, management.

|Task ID|Feature|Task                                     |Estimate|Depends On|
|-------|-------|-----------------------------------------|--------|----------|
|T6.1   |F20    |Create CoverageRequest entity            |2h      |M5        |
|T6.2   |F20    |Create EmergencyCoverageService          |4h      |T6.1      |
|T6.3   |F20    |Create replacement finder message handler|3h      |T6.2      |
|T6.4   |F20    |Add shift cancellation endpoint          |2h      |T6.2      |
|T6.5   |F20    |Write emergency coverage tests           |3h      |T6.2      |
|T6.6   |F31    |Create AlertService                      |2h      |M5        |
|T6.7   |F31    |Create alert types and templates         |2h      |T6.6      |
|T6.8   |F31    |Integrate alerts with heartbeat monitor  |2h      |T6.6      |
|T6.9   |F44    |Create alert notification handlers       |2h      |T6.6      |
|T6.10  |F35    |Create QualityMetricsService             |3h      |M5        |
|T6.11  |F35    |Add metrics calculation queries          |2h      |T6.10     |
|T6.12  |F35    |Create metrics endpoints                 |2h      |T6.11     |
|T6.13  |F37    |Create AdminDashboardService             |3h      |M5        |
|T6.14  |F37    |Create dashboard data aggregation queries|4h      |T6.13     |
|T6.15  |F37    |Create dashboard API endpoints           |2h      |T6.14     |
|T6.16  |F38    |Create operator management endpoints     |2h      |M1        |
|T6.17  |F38    |Add suspend/activate functionality       |2h      |T6.16     |
|T6.18  |F39    |Create client management endpoints       |2h      |M1        |
|T6.19  |-      |Final integration testing                |4h      |All       |
|T6.20  |-      |Documentation and API docs generation    |4h      |All       |

**Milestone Deliverable**: Full MVP - emergency coverage, admin dashboard, user management

**Total Estimate**: ~52 hours (Week 7-8)

-----

## Task Sequence (Gantt-Style)

```
Week 1:     [======== M1: Foundation ========]
            T1.1-T1.12 (Setup, Registration)

Week 2:     [==== M1 cont ====][=== M2 Start ===]
            T1.13-T1.23 (Auth)  T2.1-T2.8 (Skills/Certs)

Week 3:     [======== M2: Profiles ========]
            T2.9-T2.26 (Availability, Sites, Robots)

Week 4:     [=== M3: Matching ===][= M4 Start =]
            T3.1-T3.18            T4.1-T4.7

Week 5:     [======== M4: Scheduling ========]
            T4.8-T4.18 (Assignment, Notifications)

Week 6:     [======== M5: Real-Time ========]
            T5.1-T5.14 (Sessions, Heartbeats)

Week 7:     [== M5 cont ==][==== M6 Start ====]
            T5.15-T5.21   T6.1-T6.9 (Emergency, Alerts)

Week 8:     [======== M6: Admin ========]
            T6.10-T6.20 (Metrics, Dashboard, Docs)
```

-----

## Risk Mitigation Tasks

|Risk                      |Mitigation Task                                    |When  |
|--------------------------|---------------------------------------------------|------|
|WebSocket complexity      |T1.3: Evaluate Mercure early, have polling fallback|Week 1|
|Timezone bugs             |T2.14: Comprehensive DST edge case tests           |Week 3|
|Assignment race conditions|T4.10: Pessimistic locking from start              |Week 5|
|Scope creep               |Review against MVP scope at Milestone 3            |Week 4|
|Performance at scale      |Load test matching queries at M3                   |Week 4|

-----

## Definition of Done

A task is complete when:

- [ ] Code implemented and self-reviewed
- [ ] Unit tests written and passing
- [ ] Integration test if service-level feature
- [ ] Endpoint documented in OpenAPI spec
- [ ] No PHPStan level 6 errors
- [ ] Migrations run cleanly
- [ ] Manual smoke test passes

-----

## Project Structure

```
teleops-scheduler/
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   ├── mercure.yaml
│   │   ├── messenger.yaml
│   │   └── security.yaml
│   ├── routes/
│   │   ├── api_operators.yaml
│   │   ├── api_clients.yaml
│   │   └── api_admin.yaml
│   └── services.yaml
├── migrations/
├── src/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── OperatorController.php
│   │   │   ├── ClientController.php
│   │   │   ├── JobController.php
│   │   │   ├── ShiftController.php
│   │   │   ├── SessionController.php
│   │   │   └── AdminController.php
│   ├── Entity/
│   │   ├── Operator.php
│   │   ├── ClientOrganization.php
│   │   ├── ClientUser.php
│   │   ├── Site.php
│   │   ├── Robot.php
│   │   ├── Job.php
│   │   ├── Shift.php
│   │   ├── Session.php
│   │   └── ...
│   ├── Repository/
│   ├── Service/
│   │   ├── Registration/
│   │   ├── Matching/
│   │   │   ├── SkillMatchingService.php
│   │   │   ├── AvailabilityMatchingService.php
│   │   │   ├── LatencyMatchingService.php
│   │   │   └── MatchingService.php
│   │   ├── Scheduling/
│   │   │   ├── ShiftGenerationService.php
│   │   │   ├── ShiftAssignmentService.php
│   │   │   └── EmergencyCoverageService.php
│   │   ├── Session/
│   │   │   ├── SessionService.php
│   │   │   ├── HeartbeatService.php
│   │   │   └── HandoffService.php
│   │   └── Notification/
│   ├── Security/
│   │   ├── Voter/
│   │   └── UserProvider/
│   ├── Message/
│   │   └── Handler/
│   ├── Command/
│   │   ├── HeartbeatMonitorCommand.php
│   │   ├── ShiftReminderCommand.php
│   │   └── AutoAssignCommand.php
│   └── EventSubscriber/
├── templates/
│   └── email/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Functional/
├── docker/
│   ├── docker-compose.yml
│   └── Dockerfile
└── README.md
```

-----

## Ready for Development

With this Implementation Plan complete:

1. ✅ Vision documented
1. ✅ Features identified and prioritized
1. ✅ Specifications detailed
1. ✅ Dependencies mapped
1. ✅ Tasks broken down with estimates

**Next steps:**

1. Set up project repository
1. Create initial Docker environment
1. Begin Milestone 1 tasks
1. Track progress in project management tool (Linear, Jira, GitHub Projects)

-----

*TeleOps Scheduling Engine - Complete Development Plan*
*Estimated completion: 8 weeks (solo developer)*
