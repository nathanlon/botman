import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import RegisterForm from '@/components/auth/RegisterForm.vue'
import { useAuthStore } from '@/stores/auth'

describe('RegisterForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders registration form correctly', () => {
    const wrapper = mount(RegisterForm)

    expect(wrapper.find('h2').text()).toBe('Create Account')
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('input#firstName').exists()).toBe(true)
    expect(wrapper.find('input#lastName').exists()).toBe(true)
  })

  it('has user type toggle buttons', () => {
    const wrapper = mount(RegisterForm)

    const buttons = wrapper.findAll('.user-type-toggle button')
    expect(buttons.length).toBe(2)
    expect(buttons[0].text()).toBe('Operator')
    expect(buttons[1].text()).toBe('Client')
  })

  it('defaults to operator user type', () => {
    const wrapper = mount(RegisterForm)

    const operatorButton = wrapper.find('.user-type-toggle button:first-child')
    expect(operatorButton.classes()).toContain('active')
  })

  it('switches to client mode when client button clicked', async () => {
    const wrapper = mount(RegisterForm)

    const clientButton = wrapper.find('.user-type-toggle button:last-child')
    await clientButton.trigger('click')

    expect(clientButton.classes()).toContain('active')
  })

  it('shows company name field for client registration', async () => {
    const wrapper = mount(RegisterForm)

    // Click client button
    await wrapper.find('.user-type-toggle button:last-child').trigger('click')

    expect(wrapper.find('input#companyName').exists()).toBe(true)
  })

  it('hides company name field for operator registration', () => {
    const wrapper = mount(RegisterForm)

    expect(wrapper.find('input#companyName').exists()).toBe(false)
  })

  it('submits operator registration', async () => {
    const wrapper = mount(RegisterForm)
    const store = useAuthStore()
    store.register = vi.fn().mockResolvedValue({ message: 'Success', userId: 1 })

    await wrapper.find('input[type="email"]').setValue('operator@test.com')
    await wrapper.find('input[type="password"]').setValue('SecurePass123!')
    await wrapper.find('input#firstName').setValue('John')
    await wrapper.find('input#lastName').setValue('Doe')
    await wrapper.find('form').trigger('submit.prevent')

    expect(store.register).toHaveBeenCalledWith(expect.objectContaining({
      email: 'operator@test.com',
      password: 'SecurePass123!',
      firstName: 'John',
      lastName: 'Doe',
      userType: 'operator',
    }))
  })

  it('submits client registration with company name', async () => {
    const wrapper = mount(RegisterForm)
    const store = useAuthStore()
    store.register = vi.fn().mockResolvedValue({ message: 'Success', userId: 1, organizationId: 1 })

    // Switch to client
    await wrapper.find('.user-type-toggle button:last-child').trigger('click')

    await wrapper.find('input[type="email"]').setValue('client@company.com')
    await wrapper.find('input[type="password"]').setValue('SecurePass123!')
    await wrapper.find('input#firstName').setValue('Jane')
    await wrapper.find('input#lastName').setValue('Smith')
    await wrapper.find('input#companyName').setValue('Acme Corp')
    await wrapper.find('form').trigger('submit.prevent')

    expect(store.register).toHaveBeenCalledWith(expect.objectContaining({
      email: 'client@company.com',
      password: 'SecurePass123!',
      firstName: 'Jane',
      lastName: 'Smith',
      userType: 'client',
      companyName: 'Acme Corp',
    }))
  })

  it('emits success event after successful registration', async () => {
    const wrapper = mount(RegisterForm)
    const store = useAuthStore()
    store.register = vi.fn().mockResolvedValue({ message: 'Success', userId: 1 })

    await wrapper.find('input[type="email"]').setValue('test@test.com')
    await wrapper.find('input[type="password"]').setValue('SecurePass123!')
    await wrapper.find('input#firstName').setValue('Test')
    await wrapper.find('input#lastName').setValue('User')
    await wrapper.find('form').trigger('submit.prevent')

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.emitted('success')).toBeTruthy()
  })

  it('displays error message on registration failure', async () => {
    const wrapper = mount(RegisterForm)
    const store = useAuthStore()
    store.register = vi.fn().mockRejectedValue(new Error('Email already exists'))

    await wrapper.find('input[type="email"]').setValue('existing@test.com')
    await wrapper.find('input[type="password"]').setValue('SecurePass123!')
    await wrapper.find('input#firstName').setValue('Test')
    await wrapper.find('input#lastName').setValue('User')
    await wrapper.find('form').trigger('submit.prevent')

    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.error').exists()).toBe(true)
    expect(wrapper.find('.error').text()).toContain('Email already exists')
  })

  it('requires all mandatory fields', () => {
    const wrapper = mount(RegisterForm)

    expect((wrapper.find('input[type="email"]').element as HTMLInputElement).required).toBe(true)
    expect((wrapper.find('input[type="password"]').element as HTMLInputElement).required).toBe(true)
    expect((wrapper.find('input#firstName').element as HTMLInputElement).required).toBe(true)
    expect((wrapper.find('input#lastName').element as HTMLInputElement).required).toBe(true)
  })

  it('disables submit button while loading', async () => {
    const wrapper = mount(RegisterForm)
    const store = useAuthStore()
    store.register = vi.fn().mockImplementation(() => new Promise(resolve => setTimeout(resolve, 1000)))

    await wrapper.find('input[type="email"]').setValue('test@test.com')
    await wrapper.find('input[type="password"]').setValue('SecurePass123!')
    await wrapper.find('input#firstName').setValue('Test')
    await wrapper.find('input#lastName').setValue('User')

    const submitButton = wrapper.find('button[type="submit"]')
    await wrapper.find('form').trigger('submit.prevent')

    expect((submitButton.element as HTMLButtonElement).disabled).toBe(true)
  })
})
