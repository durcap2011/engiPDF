<script setup lang="ts">
import { computed } from 'vue'
import type { StampElement } from '../types'

const props = defineProps<{
  element: StampElement
}>()

const presets: Record<string, { border: string; bg: string; text: string }> = {
  approved: { border: '#0a0', bg: 'rgba(0,170,0,0.1)', text: 'APPROVATO' },
  confidential: { border: '#a00', bg: 'rgba(170,0,0,0.1)', text: 'RISERVATO' },
  draft: { border: '#888', bg: 'rgba(136,136,136,0.1)', text: 'BOZZA' },
  paid: { border: '#00a', bg: 'rgba(0,0,170,0.1)', text: 'PAGATO' },
  urgent: { border: '#a0a', bg: 'rgba(170,0,170,0.1)', text: 'URGENTE' },
}

const style = computed(() => {
  const e = props.element
  const p = presets[e.preset] || presets.approved
  return {
    width: '100%',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    border: `3px solid ${p.border}`,
    backgroundColor: p.bg,
    borderRadius: '8px',
    transform: 'rotate(-15deg)',
    fontWeight: 'bold',
    fontSize: '14pt',
    color: p.border,
    letterSpacing: '2px',
    opacity: 0.8,
    userSelect: 'none' as const,
  }
})
</script>

<template>
  <div :style="style">{{ element.text || presets[element.preset]?.text }}</div>
</template>
