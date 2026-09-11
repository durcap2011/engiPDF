# engiPDF - User Guide

## Introduction

engiPDF is a web editor for creating PDF templates. It allows you to arrange text elements, images, lists, rectangles, and lines on one or more virtual sheets, and to generate a PDF file via a native PHP engine. It supports multi-page documents with independent pages. The interface is available in 5 languages (Italian, English, Spanish, German, French) selectable from the toolbar.

---

## Editor Interface

The editor consists of four main areas:

```
┌─────────────────────────────────────────────────────────┐
│                    Toolbar                               │
├────────┬──────────────────────────────┬─────────────────┤
│        │  ┌─────────────────────────┐ │                 │
│Palette │  │ Horizontal Ruler        │ │   Properties    │
│Compon- │  ├─────┬───────────────────┤ │   Panel         │
│ents    │  │Ver. │                   │ │                 │
│        │  │Rul. │   Canvas Area     │ │                 │
│        │  │     │                   │ │                 │
│        │  └─────┴───────────────────┘ │                 │
└────────┴──────────────────────────────┴─────────────────┘
```

### 1. Toolbar (at the top)

The toolbar contains:
- **Logo and document name**: displays the current project name
- **Undo/Redo**: go back to the previous action or restore the next one (up to 50 levels)
- **Layer movement**: move the selected element forward/backward in the stack
- **Duplicate**: create a copy of the selected element
- **Delete**: remove the selected element
- **Import/Export**: save or load the template in JSON format
- **Page controls**: manage multiple pages of the document
- **Light/Dark theme**: toggle between light and dark interface theme

The page management controls are positioned in the toolbar and allow you to:

| Control | Description |
|---|---|
| **◀ / ▶** | Navigate between previous and next pages |
| **Page counter** | Shows the current page and total number (e.g., "2 / 5") |
| **Add page** | Inserts a new blank page after the current one |
| **Duplicate page** | Creates a copy of the current page with all its elements |
| **Delete page** | Removes the current page (not available if it's the only page) |

### 2. Component Palette (on the left)

List of available components for dragging onto the editing area, organized in categories with accordion:

| Category | Components |
|---|---|
| **Basic shapes** | Rectangle, Circle, Line, Divider |
| **Text** | Text, List, Quote, Code block |
| **Data** | Table, Barcode, QR Code, Chart |
| **Media** | Image, Icon |
| **Layout** | Container, Group, Spacer, Page break |
| **Dynamic** | Page No., Date, Progress bar |
| **Style** | Watermark, Stamp, Callout, Signature |

**How to insert a component**: drag the desired component from the palette and drop it onto the editing area. The element will be positioned with snap to the nearest grid.

### 3. Canvas Area and Rulers

The central area is the main editing area, equipped with:

- **Horizontal ruler** (at the top): shows the scale in centimeters with marks for every millimeter. It automatically syncs with pan and zoom of the canvas area.
- **Vertical ruler** (on the left): shows the scale in centimeters with marks for every millimeter. It automatically syncs with pan and zoom of the canvas area.
- **Pages**: represents the white sheets with configurable dimensions (default A4: 210×297 mm). Multiple pages are arranged vertically in the editing area, separated by a visual divider. Each page has its own independent settings (dimensions, header, footer)
- **Background grid**: slightly visible grid for orientation

**Navigation**:
- **Pan** (movement): drag the mouse on the canvas background (cursor changes to "hand" shape)
- **Zoom**: hold `Ctrl` and use the mouse wheel to zoom in/out (range: 0.2x – 5x)
- **Centering**: on first load, the page is automatically centered in the available area
- **Current page**: the page currently being edited is highlighted; elements from other pages are displayed with reduced opacity

### 4. Properties Panel (on the right)

Shows and allows you to modify the properties of the selected element or page. All panel labels and strings are translated via vue-i18n in the 5 supported languages.

**Page properties** (independent for each page):
- Paper format (A4, A3, Letter, custom)
- Dimensions (↔ width icon, ↕ height icon in mm)
- Margins (Y↑ top icon, X→ right icon, Y↓ bottom icon, ←X left icon)
- Header/Footer (⊤ header height icon, ⊥ footer height icon in mm)
- **Copy Header from**: select a source page to copy header elements from
- **Copy Footer from**: select a source page to copy footer elements from

**Element properties** (vary by type):
- Position (x, y in mm)
- Dimensions (width, height in mm)
- Style (font, color, alignment, etc.)

---

## Advanced Features

### Light/Dark Theme

The editor supports two visual themes: light and dark. To toggle between themes, click the moon/sun button in the toolbar. The preference is automatically saved in the browser.

### Multiple Selection

To select multiple elements simultaneously:
- **Shift+Click** on each element to add to the selection
- Selected elements are highlighted with a blue border
- Dragging a selected element moves all selected elements
- The properties panel shows "N elements selected"

### Alignment and Distribution

When two or more elements are selected, the alignment buttons appear in the toolbar:

| Button | Description |
|---|---|
| **Align left** | Aligns all elements to the left edge |
| **Align center** | Aligns all elements to the horizontal center |
| **Align right** | Aligns all elements to the right edge |
| **Align top** | Aligns all elements to the top edge |
| **Align vertical center** | Aligns all elements to the vertical center |
| **Align bottom** | Aligns all elements to the bottom edge |

When three or more elements are selected, the distribution buttons are also available:

| Button | Description |
|---|---|
| **Distribute horizontally** | Distributes elements with uniform horizontal spacing |
| **Distribute vertically** | Distributes elements with uniform vertical spacing |

### Grouping

To group multiple elements into a single block:
1. Select the elements to group (Shift+Click)
2. Click the "Group" button in the toolbar (or `Ctrl+G`)
3. The elements are combined into a group
4. The group can be moved and resized as a single element
5. To ungroup, select the group and click "Ungroup" (or `Ctrl+Shift+G`)

### Copy and Paste Between Pages

To copy elements from one page to another:
1. Select the elements to copy
2. Press `Ctrl+C` to copy
3. Navigate to the destination page
4. Press `Ctrl+V` to paste
5. The elements are pasted with a 5mm offset

### Find and Replace

To find and replace text in the document:
1. Press `Ctrl+F` to open the search bar
2. Enter the text to search for
3. Use the ◀/▶ buttons or press Enter/Shift+Enter to navigate between results
4. Enter the replacement text in the second field
5. Click "R" to replace the current result or "RA" to replace all
6. Press Esc to close the bar

### Layers Panel

The layers panel (on the left) shows all elements of the current page:
- **Selection**: click on an element in the list to select it
- **Visibility**: click the eye icon to hide/show an element
- **Lock**: click the lock icon to lock/unlock an element
- Hidden elements are displayed with reduced opacity
- Locked elements cannot be selected or modified on the canvas

### Selection and Movement

- **Click** on an element to select it. 8 resize handles will appear (4 corners + 4 sides).
- **Shift+Click** on an element to add it to the selection (multiple selection).
- **Drag** a selected element to move it. Positioning follows the snap grid.
- When multiple elements are selected, dragging one element moves all selected elements.
- **Click** on the background to deselect all elements.
- **Multi-page selection**: elements from other pages are displayed with reduced opacity and cannot be selected; only elements of the current page can be selected and modified.

### Resizing

- Drag one of the 8 handles to resize the element.
- Minimum size: 5mm per side.
- **Double-click** on a handle for **auto-sizing**: the element automatically adapts to its content (useful for text and lists).

### Inline Editing

- **Double-click** on a text element to edit the content directly on the editing area.
- **Double-click** on a list element to edit the list items.
- For lists: `Tab` increases indentation, `Ctrl+Tab` decreases it.
- Press `Escape` or click outside to exit editing.

### Keyboard Shortcuts

| Combination | Action |
|---|---|
| `Ctrl+Z` | Undo |
| `Ctrl+Shift+Z` / `Ctrl+Y` | Redo |
| `Ctrl+D` | Duplicate selected element |
| `Delete` | Delete selected element |
| `Ctrl+C` | Copy selected elements |
| `Ctrl+V` | Paste copied elements |
| `Ctrl+G` | Group selected elements |
| `Ctrl+Shift+G` | Ungroup selected group |
| `Ctrl+F` | Open/close search bar |

---

## Multiple Page Management

engiPDF supports documents with multiple pages. Each page is an independent unit with its own settings and elements.

### Page Creation and Management

- **Default page**: each new document starts with a single page
- **Add page**: click the "+" button in the toolbar to insert a new blank page after the current one
- **Duplicate page**: copies all elements of the current page to a new page
- **Delete page**: removes the current page (not available with only one page)
- **Navigation**: use the ◀/▶ arrows or the page counter to navigate between pages

### Page Organization

Pages are arranged vertically in the editing area:
- Each page is separated by a visual divider with the page number
- The current page is highlighted and elements from other pages are displayed with reduced opacity
- Vertical scrolling of the canvas area shows all pages in sequence

### Independent Page Properties

Each page has its own configurable settings in the properties panel:
- **Paper format**: A4, A3, Letter, custom
- **Dimensions**: ↔ width icon, ↕ height icon in mm
- **Margins**: Y↑ top icon, X→ right icon, Y↓ bottom icon, ←X left icon
- **Header/Footer**: ⊤ header height icon, ⊥ footer height icon in mm

### Element-to-Page Association

Each element is associated with a specific page via the `pageId` field:
- Elements are positioned only on the page they belong to
- When an element is dragged beyond the boundaries of its page, an "Off page" indicator is shown
- The indicator includes a button to automatically move the element to the next page
- Elements cannot be manually moved between pages; movement is only done via the "Move to next page" button

### Overlap Indicators

When an element exceeds the page boundaries:
- An "Off page" banner is displayed with a "Move to next page" button
- Clicking the button automatically moves the element to the next page while maintaining its relative coordinates
- If the element is resized within the boundaries, the indicator automatically disappears

---

## Element Types

### Text
Free text element with customizable styles:
- **Font**: Helvetica, Times, Courier and variants (Bold, Italic, BoldItalic)
- **Custom fonts**: supports TTF via upload in the properties panel
- **Size**: in points
- **Color**: RGB
- **Alignment**: left, center, right, justified
- **Style**: bold, italic, underline
- **Line height**: line height ratio

### Rectangle
Rectangular shape with:
- **Fill color**: optional RGB
- **Stroke color**: optional RGB
- **Stroke width**: in points

### Line
Line with:
- **Color**: RGB
- **Width**: in points
- Endpoints defined by coordinates (x, y) and (x2, y2)

### List
Bulleted or numbered list with:
- **Bullet type**: circle, square, dash, diamond, arrow, number
- **Nesting levels**: recursive support with `Tab` / `Ctrl+Tab`
- **Text style**: same as text strings
- **Indentation**: configurable bullet and text spacing

### Image
Image area:
- **Supported formats**: JPG, PNG, SVG (via upload)
- **Fit mode**: contain, cover, stretch
- **Double-click** to change image

### Table
Table with headers and data rows:
- **Name**: table identifier, used as a placeholder for data (`{{ name }}`)
- **Columns**: configurable number, with proportional widths
- **Headers**: individual text and style per column (font, size, bold, italic, color, alignment)
- **Data cell style**: default settings applicable to all cells, with customizable background
- **Data rows**: each cell can have a different style (alignment, bold, background color, etc.)
- **Repeat header**: checkbox to repeat the first row on every new page
- **Dynamic data**: pass a PHP array with the same name as the table to populate the rows
- **Auto pagination**: when the table exceeds the available area between header and footer, it is automatically split across multiple pages with header repetition

### Group
Container to group multiple elements:
- **Grouping**: combines multiple elements into a single block
- **Movement**: the group can be moved as a single element
- **Resizing**: the group can be resized
- **Ungrouping**: the group can be ungrouped to restore the original elements

---

## Rulers

The editor includes two rulers synchronized with the editing area, similar to those in Microsoft Word:

### Horizontal Ruler (at the top)
- Positioned at the top of the canvas area, to the right of the component palette
- Shows the scale in centimeters with marks for every millimeter
- Major (numbered) marks every centimeter
- Medium marks every 5mm
- Minor marks every mm
- Updates automatically during pan and zoom
- Coordinates refer to the current page

### Vertical Ruler (on the left)
- Positioned on the left side of the canvas area, below the toolbar
- Shows the scale in centimeters with marks for every millimeter
- Numbers rotated vertically for readability
- Updates automatically during pan and zoom
- Coordinates refer to the current page

### Intersection Corner
- Gray area in the upper-left corner where the two rulers meet
- Serves as a visual reference area

---

## Export and Import

### Export JSON
Save the entire template in JSON format, including:
- Page settings (multiple)
- All elements with their properties
- Embedded images (base64)
- Custom fonts (references)

### Import JSON
Load a previously saved JSON template from the editor.

### Paste JSON
Paste a handwritten JSON directly into the editor. The system automatically handles:
- **Missing IDs**: generates UUIDs for pages and elements
- **Missing pageId**: assigns elements to the first page
- **Missing settings**: applies default values (A4 format, standard margins)
- **Optional fields**: fills in missing fields with default values

This allows importing JSON templates created manually following the format described in [`template.md`](../template.md).

### Generate PDF
Use the JSON template to generate a PDF file via the native PHP engine:
- Open the terminal in the `engine/` folder
- Run: `php generate.php template.json output.pdf`
- The PDF will contain all pages defined in the template in the specified order

---

## Document Format (JSON)

The template is a JSON file. The current version is version 2, which supports multiple pages.

> **Complete reference**: For a JSON example with **all components** and **all supported properties**, see the [`template.md`](../template.md) file in the project root.

### Version 2 Format (current)

```json
{
  "version": 2,
  "name": "Document Name",
  "pages": [
    {
      "id": "page-1",
      "width": 210,
      "height": 297,
      "unit": "mm",
      "margins": { "top": 30, "right": 20, "bottom": 30, "left": 20 },
      "headerHeight": 15,
      "footerHeight": 10
    },
    {
      "id": "page-2",
      "width": 210,
      "height": 297,
      "unit": "mm",
      "margins": { "top": 30, "right": 20, "bottom": 30, "left": 20 },
      "headerHeight": 20,
      "footerHeight": 20
    }
  ],
  "defaultFont": "helvetica",
  "elements": [
    {
      "type": "text",
      "pageId": "page-1",
      "x": 20,
      "y": 20,
      "width": 170,
      "height": 15,
      "text": "Page 1 Title",
      "style": {
        "font": "helvetica",
        "weight": "bold",
        "size": 24,
        "color": [0, 0, 0],
        "align": "left"
      }
    },
    {
      "type": "text",
      "pageId": "page-2",
      "x": 20,
      "y": 20,
      "width": 170,
      "height": 15,
      "text": "Page 2 Content",
      "style": {
        "font": "times",
        "weight": "normal",
        "size": 12,
        "color": [0, 0, 0],
        "align": "left"
      }
    }
  ]
}
```

### Version 2 Structure

| Field | Type | Description |
|---|---|---|
| `version` | number | Format version (must be 2) |
| `name` | string | Document name |
| `pages` | array | Array of page objects, each with its own settings |
| `pages[].id` | string | Unique page identifier |
| `pages[].width` | number | Page width in mm |
| `pages[].height` | number | Page height in mm |
| `pages[].margins` | object | Page margins (top, right, bottom, left) |
| `pages[].headerHeight` | number | Header area height in mm |
| `pages[].footerHeight` | number | Footer area height in mm |
| `pages[].headerSourcePageId` | string | Source page ID for copying header (optional) |
| `pages[].footerSourcePageId` | string | Source page ID for copying footer (optional) |
| `defaultFont` | string | Default font for the document |
| `elements` | array | Array of all elements from all pages |
| `elements[].pageId` | string | ID of the page the element belongs to |

### Backward Compatibility

Version 1 templates are automatically migrated on opening:
- The single `page` field is converted to a `pages[]` array with a single element
- Existing `elements` are automatically assigned to the first page
- The format version is updated from 1 to 2
- The migration is transparent and does not modify the existing layout

### Version 1 Format (obsolete)

```json
{
  "version": 1,
  "name": "Document Name",
  "page": {
    "width": 210,
    "height": 297,
    "unit": "mm",
    "margins": { "top": 20, "right": 20, "bottom": 20, "left": 20 },
    "headerHeight": 15,
    "footerHeight": 10
  },
  "defaultFont": "helvetica",
  "elements": [...]
}
```

**Note**: version 1 format is obsolete. All new templates should use version 2.

---

## Programmability

engiPDF supports conditional logic and dynamic content generation directly in JSON templates.

### Conditional Visibility (showIf)

Each element can have a `showIf` field that controls its visibility based on data:

```json
{
  "type": "text",
  "text": "Discount applied",
  "showIf": { "field": "order.status", "op": "eq", "value": "discounted" }
}
```

**Available operators**: `eq`, `neq`, `gt`, `lt`, `gte`, `lte`, `empty`, `notempty`, `contains`

If the condition is not met, the element is not rendered in the PDF nor displayed in the editor.

**How to set a condition in the editor**:
1. Select the element on the canvas
2. In the Properties Panel (right), scroll to "Programmability"
3. Enable the "Visibility condition" checkbox
4. In the "Field" field, select the data path from the dropdown (e.g., `order.status → "paid"`)
5. Choose the operator (e.g., "Equals")
6. Enter the value to compare
7. In the editor you will see the preview immediately: if `order.status` in the sampleData is "paid", the element is visible

If the field you're looking for is not in the dropdown, select "Custom..." to type the path manually.

### Conditional Style (styleIf)

Each element can have a `styleIf` field that modifies the style based on data:

```json
{
  "type": "text",
  "text": "Status: {{ order.status }}",
  "styleIf": [
    { "field": "order.status", "op": "eq", "value": "paid", "then": { "fill": [0, 0.6, 0] } },
    { "field": "order.status", "op": "eq", "value": "overdue", "then": { "fill": [0.8, 0, 0] } }
  ]
}
```

The first rule that returns true applies the style defined in `then`. The `fill` field sets the text color.

### Repeat on All Pages (repeatOnAllPages)

Some elements can be automatically repeated on every page of the document:

1. Select the element on the canvas
2. In the Properties Panel (right), scroll to "Programmability"
3. Enable the "Repeat on all pages" checkbox

**Supported elements**: Stamp, Watermark, Text, Image, Rectangle, Line, Ellipse, Divider

**Use case example**: A "DRAFT" or "CONFIDENTIAL" stamp that should appear on every page of the document.

### Data Repeat

The "Data repeat" component automatically generates copies of its child elements for each element in a data array:

1. Drag "Data repeat" from the palette
2. Set the "Data field" (e.g., `items`)
3. Add child elements as a container
4. In child texts use `{{ item.name }}`, `{{ item.price }}` for the current element fields

### Placeholder Filters

`{{ }}` placeholders support filters for data formatting:

| Filter | Example | Description |
|--------|---------|-------------|
| `currency` | `{{ amount \| currency }}` | Currency format (e.g., €1,234.56) |
| `date:"format"` | `{{ date \| date:"d/m/Y" }}` | PHP date format |
| `number:N` | `{{ value \| number:2 }}` | N decimal digits |
| `uppercase` | `{{ text \| uppercase }}` | Uppercase |
| `lowercase` | `{{ text \| lowercase }}` | Lowercase |
| `capitalize` | `{{ text \| capitalize }}` | Capitalize first letter |
| `trim` | `{{ text \| trim }}` | Removes spaces |
| `truncate:N` | `{{ text \| truncate:20 }}` | Truncates to N characters |
| `default:"val"` | `{{ text \| default:"N/A" }}` | Default value |
| `len` | `{{ list \| len }}` | Array length |
| `if:"match":"then":"else"` | `{{ val \| if:"yes":"Ok":"No" }}` | Inline conditional |

**Array access**: `{{ items[0].name }}` accesses the first element.

**Filter chaining**: `{{ text \| uppercase \| truncate:10 }}` applies uppercase first then truncates.

### Components with Placeholder Support

The following components support `{{ variable }}` syntax in their text fields:

| Component | Supported field(s) | Example |
|------------|-------------------|---------|
| **Text** | `text` | `{{ customer_name }}` |
| **List** | `items[].text` | `{{ item.description }}` |
| **Table** | `columns[].header`, `rows[].cells[].text` | `{{ column.name }}` |
| **Quote** | `text`, `author` | `{{ quote.text }}`, `{{ quote.author }}` |
| **Callout** | `text` | `{{ message }}` |
| **Checklist** | `items[].text` | `{{ item.description }}` |
| **Radio** | `items[].text` | `{{ option.label }}` |
| **Watermark** | `text` | `{{ company }} - DRAFT` |
| **Stamp** | `text` | `{{ status }}` |
| **Barcode** | `text` | `{{ product_code }}` |
| **QR Code** | `text` | `{{ registration_link }}` |
| **Code Block** | `text` | `{{ generated_code }}` |
| **Signature** | `label` | `{{ signatory }}` |
| **Progress Bar** | `label` | `{{ percentage }}%` |

### Checklist

The "Checklist" component displays a list with checkboxes (□ ☑):

```json
{
  "type": "checklist",
  "items": [
    { "text": "Documents signed", "checked": true },
    { "text": "Payment received", "checked": false }
  ],
  "size": 11,
  "color": [0, 0, 0],
  "checkedColor": [0, 0.6, 0],
  "gap": 3
}
```

**Properties**:
- `items`: array of `{ text, checked }` objects
- `size`: font size in points
- `color`: text color [R, G, B]
- `checkedColor`: checked checkbox color [R, G, B]
- `gap`: vertical spacing between items in mm

In the editor you can click the checkboxes for preview, add/remove items from the Properties Panel.

### Radio

The "Radio" component displays a list with radio buttons (○ ●):

```json
{
  "type": "radio",
  "items": [
    { "text": "Option A", "selected": true },
    { "text": "Option B", "selected": false },
    { "text": "Option C", "selected": false }
  ],
  "size": 11,
  "color": [0, 0, 0],
  "selectedColor": [0, 0.5, 1],
  "gap": 3
}
```

**Properties**:
- `items`: array of `{ text, selected }` objects
- `size`: font size in points
- `color`: text color [R, G, B]
- `selectedColor`: selected circle color [R, G, B]
- `gap`: vertical spacing between items in mm

Unlike the checklist, only one option can be selected at a time.

---

## Complete Component Documentation

For detailed documentation of each component (properties, JSON+PHP examples, programmability), see the [`components-guide.md`](./components-guide.md) file.