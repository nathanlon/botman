import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import LoginForm from '@/components/auth/LoginForm.vue'
import { useAuthStore } from '@/stores/auth'

describe('LoginForm', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders login form correctly', () => {
    const wrapper = mount(LoginForm)

    expect(wrapper.find('h2').text()).toBe('Login')
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('has empty initial values', () => {
    const wrapper = mount(LoginForm)

    const emailInput = wrapper.find('input[type="email"]')
    const passwordInput = wrapper.find('input[type="password"]')

    expect((emailInput.element as HTMLInputElement).value).toBe('')
    expect((passwordInput.element as HTMLInputElement).value).toBe('')
  })

  it('updates v-model on input', async () => {
    const wrapper = mount(LoginForm)

    const emailInput = wrapper.find('input[type="email"]')
    const passwordInput = wrapper.find('input[type="password"]')

    await emailInput.setValue('test@example.com')
    await passwordInput.setValue('password123')

    expect((emailInput.element as HTMLInputElement).value).toBe('test@example.com')
    expect((passwordInput.element as HTMLInputElement).value).toBe('password123')
  })

  it('calls login on form submit', async () => {
    const wrapper = mount(LoginForm)
    const store = useAuthStore()
    store.login = vi.fn().mockResolvedValue(undefined)

    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('input[type="password"]').setValue('password123')
    await wrapper.find('form').trigger('submit.prevent')

    expect(store.login).toHaveBeenCalledWith('test@example.com', 'password123')
  })

  it('emits success event after successful login', async () => {
    const wrapper = mount(LoginForm)
    const store = useAuthStore()
    store.login = vi.fn().mockResolvedValue(undefined)

    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('input[type="password"]').setValue('password123')
    await wrapper.find('form').trigger('submit.prevent')

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.emitted('success')).toBeTruthy()
  })

  it('displays error message on login failure', async () => {
    const wrapper = mount(LoginForm)
    const store = useAuthStore()
    store.login = vi.fn().mockRejectedValue(new Error('Invalid credentials'))

    await wrapper.find('input[type="email"]').setValue('bad@example.com')
    await wrapper.find('input[type="password"]').setValue('wrongpassword')
    await wrapper.find('form').trigger('submit.prevent')

    // Wait for async operations
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.find('.error').exists()).toBe(true)
    expect(wrapper.find('.error').text()).toContain('Invalid credentials')
  })

  it('disables submit button while loading', async () => {
    const wrapper = mount(LoginForm)
    const store = useAuthStore()
    store.login = vi.fn().mockImplementation(() => new Promise(resolve => setTimeout(resolve, 1000)))

    await wrapper.find('input[type="email"]').setValue('test@example.com')
    await wrapper.find('input[type="password"]').setValue('password123')

    const submitButton = wrapper.find('button[type="submit"]')
    await wrapper.find('form').trigger('submit.prevent')

    expect((submitButton.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('requires email field', () => {
    const wrapper = mount(LoginForm)
    const emailInput = wrapper.find('input[type="email"]')
    expect((emailInput.element as HTMLInputElement).required).toBe(true)
  })

  it('requires password field', () => {
    const wrapper = mount(LoginForm)
    const passwordInput = wrapper.find('input[type="password"]')
    expect((passwordInput.element as HTMLInputElement).required).toBe(true)
  })
})
