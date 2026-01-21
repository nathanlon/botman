# TeleOps Scheduling Engine - Dependency Analysis

## Dependency Matrix

|Feature                          |Depends On      |Blocks                    |Type         |Risk  |
|---------------------------------|----------------|--------------------------|-------------|------|
|**F1: Operator Registration**    |-               |F3, F6, F7, F9, F10       |-            |Low   |
|**F2: Client Registration**      |-               |F3, F4, F11               |-            |Low   |
|**F3: Authentication**           |F1, F2          |All authenticated features|Hard         |Low   |
|**F4: Role-Based Access**        |F3              |All protected features    |Hard         |Low   |
|**F6: Skill Profiles**           |F1              |F21                       |Hard         |Low   |
|**F7: Certification Tracking**   |F1              |F21                       |Hard         |Low   |
|**F9: Availability Management**  |F1              |F22, F17                  |Hard         |Medium|
|**F10: Location & Latency**      |F1              |F24                       |Hard         |Medium|
|**F11: Site Management**         |F2              |F12, F14                  |Hard         |Low   |
|**F12: Robot Registration**      |F11             |F14, F15                  |Hard         |Low   |
|**F14: Coverage Requirements**   |F11, F12        |F16                       |Hard         |Low   |
|**F15: Job Posting**             |F2, F11, F12, F6|F16, F21                  |Hard         |Low   |
|**F16: Shift Definition**        |F15, F14        |F17, F18                  |Hard         |Low   |
|**F17: Shift Assignment**        |F16, F21-24, F9 |F20, F27                  |Hard         |High  |
|**F18: Conflict Detection**      |F16, F17        |-                         |Hard         |Low   |
|**F20: Emergency Coverage**      |F17, F21-24     |F31                       |Hard         |Medium|
|**F21: Skill Matching**          |F6              |F17                       |Hard         |Low   |
|**F22: Availability Matching**   |F9              |F17                       |Hard         |Medium|
|**F24: Latency Matching**        |F10             |F17                       |Hard         |Medium|
|**F27: Session Start/End**       |F17             |F28, F32, F35             |Hard         |Medium|
|**F28: Session State**           |F27             |F29                       |Hard         |Low   |
|**F29: Heartbeat Monitoring**    |F28, WebSocket  |F31                       |Hard/External|High  |
|**F30: Shift Handoff**           |F27, F16        |-                         |Hard         |Medium|
|**F31: Escalation Alerts**       |F29, F20        |-                         |Hard         |Low   |
|**F32: Time Tracking**           |F27             |F35                       |Hard         |Low   |
|**F35: Quality Metrics**         |F27, F32        |F36                       |Hard         |Low   |
|**F37: Admin Dashboard**         |F3, F4, F27     |-                         |Hard         |Low   |
|**F38: Operator Management**     |F1, F4          |-                         |Hard         |Low   |
|**F39: Client Management**       |F2, F4          |-                         |Hard         |Low   |
|**F40: Audit Logging**           |F3              |-                         |Soft         |Low   |
|**F42: Shift Reminders**         |F17, F45        |-                         |Hard         |Low   |
|**F43: Assignment Notifications**|F17, F45        |-                         |Hard         |Low   |
|**F44: Alert Notifications**     |F31, F45        |-                         |Hard         |Low   |
|**F45: Email Integration**       |-               |F42, F43, F44             |External     |Low   |

-----

## Dependency Graph (Simplified ASCII)

```
LAYER 0 - Foundations
┌─────────────────────────────────────────────────────────────────┐
│  F1: Operator Reg    F2: Client Reg    F45: Email Integration   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 1 - Identity & Auth
┌─────────────────────────────────────────────────────────────────┐
│  F3: Authentication  →  F4: Role-Based Access                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 2 - Profiles & Setup
┌─────────────────────────────────────────────────────────────────┐
│  OPERATOR SIDE              │  CLIENT SIDE                      │
│  F6: Skills                 │  F11: Sites                       │
│  F7: Certifications         │  F12: Robots                      │
│  F9: Availability           │  F14: Coverage                    │
│  F10: Latency               │                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 3 - Matching & Jobs
┌─────────────────────────────────────────────────────────────────┐
│  F21: Skill Match           │  F15: Job Posting                 │
│  F22: Availability Match    │                                   │
│  F24: Latency Match         │                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 4 - Scheduling
┌─────────────────────────────────────────────────────────────────┐
│  F16: Shift Definition  →  F17: Assignment  →  F18: Conflicts   │
│                             │                                   │
│  F42: Reminders            F43: Notifications    F20: Emergency │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 5 - Real-Time Sessions
┌─────────────────────────────────────────────────────────────────┐
│  F27: Session Start/End  →  F28: State  →  F29: Heartbeat       │
│  F30: Handoff               F32: Time Tracking                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
LAYER 6 - Monitoring & Admin
┌─────────────────────────────────────────────────────────────────┐
│  F31: Alerts    F35: Metrics    F37: Dashboard                  │
│  F38: Op Mgmt   F39: Client Mgmt   F40: Audit                   │
└─────────────────────────────────────────────────────────────────┘
```

-----

## Critical Path

The longest dependency chain determines minimum timeline:

**F1 → F3 → F4 → F9 → F22 → F17 → F27 → F28 → F29 → F31**

This chain spans **10 features** across **6 layers**.

-----

## Risk Analysis

### High Risk Dependencies

|Dependency         |Risk                                     |Mitigation                                                       |
|-------------------|-----------------------------------------|-----------------------------------------------------------------|
|**F29: WebSocket** |Real-time scaling, connection reliability|Use Mercure (Symfony native) or managed service; stateless design|
|**F17: Assignment**|Race conditions, algorithm complexity    |Pessimistic locking, extensive tests, feature flags              |
|**F10: Latency**   |Measurement accuracy, gaming potential   |Server-verified tests, periodic re-measurement                   |

### Medium Risk Dependencies

|Dependency                    |Risk                      |Mitigation                                      |
|------------------------------|--------------------------|------------------------------------------------|
|**F9: Availability**          |Timezone complexity, DST  |Carbon library, UTC storage, comprehensive tests|
|**F22: Availability Matching**|Query performance at scale|Redis cache, pre-computed windows               |
|**F27: Sessions**             |State consistency         |Event sourcing, idempotent operations           |

### Bottlenecks

1. **F1/F2 (Registration)** - Implement first sprint
1. **F3/F4 (Auth)** - Implement early, unblocks all protected features
1. **F17 (Assignment)** - Central integration point, high test coverage needed

-----

## Parallelization Tracks

### Track A: Operator Journey

```
F1 → F6, F7, F9, F10 → F21, F22, F24
```

### Track B: Client Journey

```
F2 → F11 → F12 → F14, F15
```

### Track C: Infrastructure

```
F45 (Email) + F3 (Auth) → F4 (RBAC) → F40 (Audit)
```

### Merge Point

```
F16 (Shifts) requires Track A + Track B complete
F17 (Assignment) requires F16 + all matching (F21, F22, F24)
```

-----

## Recommended Build Order

### Phase 1: Foundation (Weeks 1-2)

- F1: Operator Registration
- F2: Client Registration
- F3: Authentication
- F4: Role-Based Access
- F45: Email Integration
- F40: Audit Logging

**Deliverable**: Users can register and log in

### Phase 2: Profiles (Weeks 2-3)

- F6: Skill Profiles
- F7: Certification Tracking
- F9: Availability Management
- F10: Location & Latency
- F11: Site Management
- F12: Robot Registration

**Deliverable**: Full profile setup for both user types

### Phase 3: Matching & Jobs (Weeks 3-4)

- F21: Skill Matching
- F22: Availability Matching
- F24: Latency Matching
- F14: Coverage Requirements
- F15: Job Posting

**Deliverable**: Jobs can be posted with matching criteria

### Phase 4: Scheduling (Weeks 4-5)

- F16: Shift Definition
- F17: Shift Assignment
- F18: Conflict Detection
- F42: Shift Reminders
- F43: Assignment Notifications

**Deliverable**: Operators assigned to shifts

### Phase 5: Real-Time (Weeks 5-7)

- F27: Session Start/End
- F28: Session State
- F29: Heartbeat Monitoring
- F30: Shift Handoff
- F32: Time Tracking

**Deliverable**: Live session management

### Phase 6: Admin & Metrics (Weeks 7-8)

- F20: Emergency Coverage
- F31: Escalation Alerts
- F35: Quality Metrics
- F37: Admin Dashboard
- F38, F39: User Management

**Deliverable**: Full MVP operational

-----

*Next: [Implementation Plan](./05-IMPLEMENTATION.md)*
