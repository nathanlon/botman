# TeleOps Scheduling Engine Vision

## Problem Statement

Robot-owning businesses need remote teleoperators to control their robotic arms, but matching operators to jobs across global time zones, managing availability, skill requirements, and real-time handoffs is complex. Currently, no dominant marketplace exists to efficiently connect trained operators with client robots 24/7.

## Proposed Solution

Build a **global teleoperation scheduling engine** - the "Uber for robot operators" - that:
- Matches operators to jobs based on skills, availability, timezone, and latency requirements
- Enables 24/7 robot operation through intelligent global workforce scheduling
- Handles real-time session management, handoffs, and fallback coverage
- Provides fair compensation tracking and quality assurance

## Target Users

### Primary Users
- **Operators**: Remote workers worldwide who control robots via ExoArm-7 exoskeletons
  - Need: Clear schedules, fair pay, skill-matched assignments, equipment support

- **Clients (Robot Owners)**: Businesses deploying robotic arms for tasks
  - Need: Reliable 24/7 coverage, skilled operators, quality guarantees, transparent pricing

### Secondary Users
- **Platform Administrators**: Manage operators, clients, quality, disputes
  - Need: Dashboards, alerts, reporting, intervention tools

- **Training Coordinators**: Certify operators on equipment and task types
  - Need: Certification tracking, skill assessments, training materials

## Success Criteria

- [ ] Match operators to jobs with <5 minute average wait time
- [ ] Achieve 99.5% scheduled shift coverage (minimize no-shows)
- [ ] Maintain <150ms end-to-end latency for matched operator-robot pairs
- [ ] Process real-time schedule changes within 30 seconds
- [ ] Support 1,000+ concurrent active sessions in MVP
- [ ] Operator satisfaction score >4.0/5.0
- [ ] Client satisfaction score >4.0/5.0

## Scope

### In Scope (MVP)
- Operator registration, profiles, and skill/certification tracking
- Client registration, robot/site management
- Availability management (operators set working hours)
- Job posting and requirements specification
- Automated matching algorithm (skills, timezone, latency)
- Shift scheduling with conflict detection
- Real-time session tracking (active/idle/intervention)
- Automatic handoff at shift boundaries
- Emergency coverage/escalation system
- Basic compensation tracking (hours worked, rates)
- Quality metrics collection (task success, intervention rate)
- Admin dashboard for monitoring

### Out of Scope (Future Phases)
- Payment processing (integrate with Stripe/PayPal later)
- Operator training/certification delivery (assume external)
- Robot/equipment management (clients manage their own)
- AI-powered demand forecasting
- Mobile apps (web-first)
- Video streaming infrastructure (assume separate service)
- Data marketplace for AI training

## Constraints

### Technical
- **Framework**: Symfony 7.x (PHP 8.3+)
- **Database**: mySQL (time-series data, complex scheduling queries)
- **Cache**: Redis (real-time session state, availability lookups)
- **Queue**: Symfony Messenger with Redis transport
- **API**: REST + WebSocket for real-time updates
- **Time handling**: All times stored UTC, timezone-aware display

### Business
- Must support operators in minimum 3 timezone regions (APAC, EMEA, Americas)
- Must handle 1:N operator-to-robot ratios (one operator monitoring multiple robots)
- Latency matching is critical - operator must be <150ms from robot location

### Resources
- Solo developer (Nathan) initially
- Target: MVP in 8-12 weeks
- Leverage existing Symfony/Doctrine expertise

## Open Questions

1. **Latency measurement**: How do we measure/verify operator-to-robot latency before assignment?
   - Option A: Require operators to run speed tests to robot regions
   - Option B: Infer from operator location to robot datacenter
   - Option C: Real-time ping during session start, fail fast if too high

2. **Operator-to-robot ratio**: How do we handle 1:50 supervisory mode vs 1:1 active control?
   - Need different scheduling logic for "monitoring pool" vs "active control"

3. **Pricing model**: Hourly? Per-task? Subscription tiers?
   - Deferred to Phase 2, but data model should support multiple models

4. **Equipment standardization**: Do we require ExoArm-7, or support multiple exoskeletons?
   - MVP: Support equipment types as metadata, don't enforce specific hardware

5. **Quality disputes**: How do we handle client complaints about operator performance?
   - Need escalation workflow, but details TBD

---

*Next Step: [Feature Identification](./02-FEATURES.md)*
