import type { ConditionalRule, StyleRule } from '../types'

export function evaluateShowIf(rule: ConditionalRule | undefined, data: Record<string, unknown>): boolean {
  if (!rule) return true

  const fieldValue = getNestedValue(data, rule.field)
  const compareValue = rule.value ?? ''

  switch (rule.op) {
    case 'eq':
      return String(fieldValue) === compareValue
    case 'neq':
      return String(fieldValue) !== compareValue
    case 'gt':
      return Number(fieldValue) > Number(compareValue)
    case 'lt':
      return Number(fieldValue) < Number(compareValue)
    case 'gte':
      return Number(fieldValue) >= Number(compareValue)
    case 'lte':
      return Number(fieldValue) <= Number(compareValue)
    case 'empty':
      return fieldValue === null || fieldValue === undefined || fieldValue === ''
    case 'notempty':
      return fieldValue !== null && fieldValue !== undefined && fieldValue !== ''
    case 'contains':
      return String(fieldValue).includes(compareValue)
    default:
      return true
  }
}

export function evaluateStyleIf(rules: StyleRule[] | undefined, data: Record<string, unknown>): Record<string, unknown> {
  if (!rules || rules.length === 0) return {}

  for (const rule of rules) {
    if (evaluateShowIf(rule, data)) {
      return rule.then
    }
  }

  return {}
}

function getNestedValue(obj: Record<string, unknown>, path: string): unknown {
  const parts = path.split('.')
  let current: unknown = obj

  for (const part of parts) {
    if (current === null || current === undefined || typeof current !== 'object') {
      return undefined
    }
    current = (current as Record<string, unknown>)[part]
  }

  return current
}
