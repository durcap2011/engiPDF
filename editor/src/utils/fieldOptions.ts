export interface FieldOption {
  path: string
  label: string
  value: unknown
}

export function flattenSampleData(data: Record<string, unknown>, prefix = ''): FieldOption[] {
  const options: FieldOption[] = []

  for (const key of Object.keys(data)) {
    const fullPath = prefix ? `${prefix}.${key}` : key
    const val = data[key]

    if (val === null || val === undefined) {
      options.push({ path: fullPath, label: fullPath, value: val })
      continue
    }

    if (Array.isArray(val)) {
      options.push({ path: fullPath, label: `${fullPath} (array)`, value: `Array[${val.length}]` })
      val.forEach((item, i) => {
        const itemPath = `${fullPath}[${i}]`
        if (typeof item === 'object' && item !== null) {
          options.push(...flattenSampleData(item as Record<string, unknown>, itemPath))
        } else {
          options.push({ path: itemPath, label: itemPath, value: item })
        }
      })
      continue
    }

    if (typeof val === 'object') {
      options.push(...flattenSampleData(val as Record<string, unknown>, fullPath))
      continue
    }

    options.push({ path: fullPath, label: fullPath, value: val })
  }

  return options
}
