<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import type { TableElement, TableColumn, TableRow, TextStyle, TableCell } from '../types'

const props = defineProps<{
  element: TableElement
}>()

const emit = defineEmits<{
  update: [data: { columns?: TableColumn[]; rows?: TableRow[] }]
}>()

const editingCell = ref<{ row: number; col: number } | null>(null)
const editValue = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

const tableStyle = computed(() => ({
  width: '100%',
  height: '100%',
  borderCollapse: 'collapse' as const,
  tableLayout: 'fixed' as const,
}))

const fontMap: Record<string, string> = {
  helvetica: 'Helvetica, Arial, sans-serif',
  times: 'Times New Roman, serif',
  courier: 'Courier New, monospace',
  arial: 'Helvetica, Arial, sans-serif',
  serif: 'Times New Roman, serif',
  'sans-serif': 'Helvetica, Arial, sans-serif',
  monospace: 'Courier New, monospace',
}

function buildCellStyle(style: TextStyle): Record<string, string> {
  return {
    fontFamily: fontMap[style.font] || style.font,
    fontSize: `${style.size * (96 / 72)}px`,
    fontWeight: style.weight === 'bold' ? '700' : '400',
    fontStyle: style.style || 'normal',
    color: `rgb(${style.color[0] * 255}, ${style.color[1] * 255}, ${style.color[2] * 255})`,
    textAlign: style.align,
    padding: '4px 6px',
    border: '1px solid #666',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap' as const,
    cursor: 'text',
  }
}

function headerCellStyle(col: TableColumn): Record<string, string> {
  const base = buildCellStyle(col.headerStyle)
  base.backgroundColor = 'rgb(217, 217, 217)'
  return base
}

function dataCellStyle(cell: TableCell): Record<string, string> {
  const merged: TextStyle = {
    ...props.element.cellStyle,
    ...cell.style,
  }
  return buildCellStyle(merged)
}

function columnWidth(col: TableColumn): string {
  const totalColWidth = props.element.columns.reduce((sum, c) => sum + c.width, 0)
  return `${(col.width / totalColWidth) * 100}%`
}

function startEditing(row: number, col: number) {
  editingCell.value = { row, col }
  editValue.value = props.element.rows[row]?.cells[col]?.text || ''
  nextTick(() => {
    inputRef.value?.focus()
    inputRef.value?.select()
  })
}

function stopEditing() {
  if (!editingCell.value) return
  const { row, col } = editingCell.value
  const newRows = props.element.rows.map((r, ri) => {
    if (ri !== row) return r
    const newCells = r.cells.map((c, ci) => {
      if (ci !== col) return c
      return { ...c, text: editValue.value }
    })
    return { ...r, cells: newCells }
  })
  emit('update', { rows: newRows })
  editingCell.value = null
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    editingCell.value = null
  } else if (e.key === 'Enter') {
    stopEditing()
  }
}
</script>

<template>
  <div class="table-wrapper">
    <div class="table-name">{{ element.name }}</div>
    <table :style="tableStyle">
      <thead>
        <tr>
          <th
            v-for="(col, colIdx) in element.columns"
            :key="colIdx"
            :style="{ ...headerCellStyle(col), width: columnWidth(col) }"
          >
            {{ col.header }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, rowIdx) in element.rows" :key="rowIdx">
          <td
            v-for="(cell, colIdx) in row.cells"
            :key="colIdx"
            :style="dataCellStyle(cell)"
            @dblclick.stop="startEditing(rowIdx, colIdx)"
          >
            <template v-if="editingCell?.row === rowIdx && editingCell?.col === colIdx">
              <input
                ref="inputRef"
                :value="editValue"
                @input="editValue = ($event.target as HTMLInputElement).value"
                @blur="stopEditing"
                @keydown="onKeydown"
                @click.stop
                @pointerdown.stop
                :style="{
                  ...dataCellStyle(cell),
                  width: '100%',
                  border: 'none',
                  outline: '2px solid #4A90D9',
                  padding: '3px 5px',
                  margin: 0,
                  background: 'white',
                }"
              />
            </template>
            <template v-else>
              {{ cell.text }}
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.table-name {
  font-size: 9px;
  color: #999;
  padding: 1px 4px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 3px 3px 0 0;
  align-self: flex-start;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
table {
  border-collapse: collapse;
  flex: 1;
}
th, td {
  box-sizing: border-box;
}
</style>
