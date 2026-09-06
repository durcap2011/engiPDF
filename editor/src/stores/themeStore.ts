import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref<'light' | 'dark'>('light')

  function applyTheme(t: 'light' | 'dark') {
    document.documentElement.setAttribute('data-theme', t)
  }

  function toggle() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  function init() {
    const saved = localStorage.getItem('engipdf-theme') as 'light' | 'dark' | null
    theme.value = saved || 'light'
    applyTheme(theme.value)

    watch(theme, (t) => {
      applyTheme(t)
      localStorage.setItem('engipdf-theme', t)
    })
  }

  return { theme, toggle, init }
})
