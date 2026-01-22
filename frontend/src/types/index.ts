export interface User {
  id: number
  email: string
  fullName: string
  type: 'operator' | 'client'
  roles: string[]
}

export interface Operator extends User {
  type: 'operator'
  countryCode: string
  timezoneId: string
  status: 'pending' | 'active' | 'suspended' | 'deactivated'
  emailVerified: boolean
}

export interface ClientUser extends User {
  type: 'client'
  role: 'admin' | 'member'
  organization: Organization
}

export interface Organization {
  id: number
  companyName: string
  countryCode?: string
  status: string
}

export interface Site {
  id: number
  name: string
  address?: string
  regionCode: string
  timezoneId?: string
  status: string
  robotCount?: number
}

export interface Robot {
  id: number
  name: string
  model: string
  capabilities: number[]
  connectionEndpoint?: string
  status: 'offline' | 'online' | 'in_session' | 'maintenance' | 'error'
  lastSeenAt?: string
  site?: { id: number; name: string }
}

export interface Job {
  id: number
  title: string
  description?: string
  status: 'draft' | 'open' | 'in_progress' | 'completed' | 'cancelled'
  startDate: string
  endDate: string
  requiredSkillIds: number[]
  requiredCertificationTypeIds: number[]
  maxLatencyMs: number
  hourlyRateAmount: string
  hourlyRateCurrency: string
  site: { id: number; name: string; regionCode?: string }
  organization?: { companyName: string }
  robots?: Robot[]
  shifts?: Shift[]
  shiftCount?: number
  availableShifts?: number
}

export interface Shift {
  id: number
  status: 'unassigned' | 'assigned' | 'in_progress' | 'completed' | 'missed' | 'cancelled'
  startTimeUtc: string
  endTimeUtc: string
  durationMinutes: number
  assignedOperator?: { id: number; fullName: string }
  job: { id: number; title: string; hourlyRate: string; currency: string }
  site: { id: number; name: string; regionCode?: string }
}

export interface Skill {
  id: number
  skillId: number
  skillName: string
  category: string
  proficiencyLevel: number
  addedAt: string
}

export interface SkillDefinition {
  id: number
  name: string
  category: string
  description?: string
}

export interface Availability {
  id: number
  dayOfWeek: number
  startTime: string
  endTime: string
}

export interface Session {
  id: number
  status: 'active' | 'paused' | 'completed' | 'disconnected' | 'aborted'
  startedAt: string
  endedAt?: string
  durationSeconds?: number
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface OperatorRegistration {
  email: string
  password: string
  fullName: string
  countryCode: string
  timezoneId: string
}

export interface ClientRegistration {
  email: string
  password: string
  fullName: string
  companyName: string
  countryCode?: string
}

export interface ApiResponse<T> {
  data?: T
  error?: string
  errors?: Record<string, string>
  message?: string
}
