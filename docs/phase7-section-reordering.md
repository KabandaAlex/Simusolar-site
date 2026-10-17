# Phase 7: Drag-and-Drop Section Reordering

## Overview

This phase implements a visual drag-and-drop interface for reordering sections in the Tulima Solar CMS. The implementation uses SortableJS for smooth, touch-friendly drag interactions with automatic AJAX saving.

## Implementation Date

**Completed:** February 2026

## Features Implemented

### 1. Backend API Endpoint (`admin/api/sections.php`)

A new RESTful API endpoint for section management:

#### GET Endpoints

| Action | Parameters | Description |
|--------|------------|-------------|
| `list` | `page_id` (required) | Get all sections for a page ordered by sort_order |
| `get` | `section_id` (required) | Get a single section by ID |
| `all` | `page_id` (optional) | Get all sections with optional page filter |

#### POST Endpoints

| Action | Request Body | Description |
|--------|--------------|-------------|
| `reorder` | `{page_id, section_ids[]}` | Update section order for a page |
| `move-up` | `{section_id}` | Move a section up one position |
| `move-down` | `{section_id}` | Move a section down one position |
| `bulk-reorder` | `{orders: {section_id: sort_order}}` | Bulk update section orders |

#### Response Format

All responses return JSON:

```json
{
  "success": true,
  "message": "Section order updated successfully",
  "updated_count": 5
}
```

Error responses:
```json
{
  "success": false,
  "error": "Error message here"
}
```

### 2. Database Helper Functions (`includes/functions.php`)

New functions added for section management:

#### `updateSectionOrder(PDO $pdo, int $pageId, array $sectionIds): array`

Updates sort_order for all sections on a page.

```php
$result = updateSectionOrder($pdo, $pageId, [5, 3, 1, 2]);
// Returns: ['success' => true, 'message' => 'Section order updated successfully']
```

#### `getSectionsByPage(PDO $pdo, int $pageId): array`

Returns all sections for a page in correct order.

```php
$sections = getSectionsByPage($pdo, 1);
```

#### `moveSectionUp(PDO $pdo, int $sectionId): array`

Moves a section up one position.

```php
$result = moveSectionUp($pdo, 5);
// Returns: ['success' => true, 'new_position' => 2]
```

#### `moveSectionDown(PDO $pdo, int $sectionId): array`

Moves a section down one position.

```php
$result = moveSectionDown($pdo, 5);
// Returns: ['success' => true, 'new_position' => 4]
```

#### `getSectionById(PDO $pdo, int $sectionId): ?array`

Gets a single section with page info.

```php
$section = getSectionById($pdo, 5);
```

#### `getAllSections(PDO $pdo, ?int $pageId = null): array`

Gets all sections with optional page filter.

```php
$sections = getAllSections($pdo); // All sections
$sections = getAllSections($pdo, 1); // Sections for page 1
```

#### `bulkUpdateSectionOrder(PDO $pdo, array $orders): array`

Bulk updates section orders across pages.

```php
$result = bulkUpdateSectionOrder($pdo, [
    5 => 1,  // Section ID 5 -> sort_order 1
    3 => 2,  // Section ID 3 -> sort_order 2
    1 => 3   // Section ID 1 -> sort_order 3
]);
```

### 3. Page Builder Enhancements (`admin/page_builder.php`)

#### SortableJS Integration

- Uses SortableJS 1.15.0 from CDN
- Drag handle on the grip icon for precise control
- Visual feedback during drag operations
- Auto-save with 500ms debounce to prevent excessive API calls

#### Visual Feedback

- **Ghost Class**: Semi-transparent placeholder when dragging
- **Chosen Class**: Elevated card with shadow when selected
- **Drag Class**: Smooth opacity transition during drag

#### Toast Notifications

Success and error notifications appear in the bottom-right corner:

```javascript
showToast('Section order saved successfully', 'success');
showToast('Failed to save order', 'error');
```

#### Loading Indicator

A centered loading overlay appears during save operations.

#### Accessibility Features

- Keyboard navigation support (Alt+Up/Down to move sections)
- Focus indicators on section cards
- ARIA labels for screen readers
- Up/Down button fallbacks for users who can't drag

### 4. Sections Management Page (`admin/sections.php`)

#### Table Row Drag-and-Drop

- Drag handle column on the left
- Order badge showing current position
- Rows can be dragged to reorder within each page
- Automatic grouping by page

#### Filter Toolbar

- Search by page slug or section key
- Filter by page
- Filter by type (with image, with items)
- Save order button appears when changes are pending

#### Move Buttons

Each row has up/down buttons as a fallback:

```html
<button onclick="moveSectionUpRow(5)">Move Up</button>
<button onclick="moveSectionDownRow(5)">Move Down</button>
```

### 5. CSS Styles (`admin/assets/css/admin.css`)

New styles added for drag-and-drop functionality:

#### Drag Handle Styles

```css
.drag-handle {
  cursor: grab;
  padding: var(--space-2);
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  transition: all var(--transition-fast);
}

.drag-handle:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}
```

#### Sortable Row States

```css
.sortable-row--ghost { opacity: 0.4; background: var(--primary-light); }
.sortable-row--chosen { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
.sortable-row--drag { background: var(--bg-card); transform: scale(1.01); }
```

#### Toast Notification Styles

```css
.admin-toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  /* ... */
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s ease;
}

.admin-toast.show {
  transform: translateY(0);
  opacity: 1;
}
```

## Technical Requirements

### Dependencies

- **SortableJS 1.15.0**: https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js
- No additional npm packages required

### Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Touch devices (iOS Safari, Android Chrome)

### Database Schema

Uses existing `page_sections` table:

```sql
CREATE TABLE page_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    title VARCHAR(255),
    content TEXT,
    content_extra TEXT,
    image_path VARCHAR(500),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (page_id) REFERENCES pages(id)
);
```

## Usage Guide

### For Content Editors

#### In Page Builder

1. Navigate to **Pages** → **Page Builder** for the desired page
2. Hover over a section card to see the drag handle (grip icon)
3. Click and drag the handle to reorder sections
4. Release to drop - the order saves automatically
5. A success toast confirms the save

#### In Sections Management

1. Navigate to **Sections** in the admin menu
2. Use the page filter to show only relevant sections
3. Drag rows using the grip icon on the left
4. Or use the up/down arrow buttons for precise control
5. Changes save automatically with visual feedback

### Keyboard Navigation

For users who prefer keyboard navigation:

1. Tab to focus a section card
2. Press **Alt+Up** to move the section up
3. Press **Alt+Down** to move the section down
4. Changes save automatically

## API Usage Examples

### JavaScript Fetch API

```javascript
// Reorder sections
async function reorderSections(pageId, sectionIds) {
    const response = await fetch('api/sections.php?action=reorder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page_id: pageId, section_ids: sectionIds })
    });
    return response.json();
}

// Move section up
async function moveUp(sectionId) {
    const response = await fetch('api/sections.php?action=move-up', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ section_id: sectionId })
    });
    return response.json();
}

// Get sections for a page
async function getSections(pageId) {
    const response = await fetch(`api/sections.php?action=list&page_id=${pageId}`);
    return response.json();
}
```

### PHP Usage

```php
require_once 'includes/functions.php';

// Get sections for a page
$sections = getSectionsByPage($pdo, $pageId);

// Update order
$result = updateSectionOrder($pdo, $pageId, [5, 3, 1, 2]);
if ($result['success']) {
    echo "Updated {$result['updated_count']} sections";
}

// Move a section
$result = moveSectionUp($pdo, $sectionId);
if (!$result['success']) {
    echo "Error: " . $result['error'];
}
```

## Troubleshooting

### Sections Not Saving

1. Check browser console for JavaScript errors
2. Verify the API endpoint is accessible at `admin/api/sections.php`
3. Ensure the user is logged in as an admin
4. Check database connection

### Drag Not Working

1. Ensure SortableJS is loaded (check Network tab)
2. Verify the section list container has the correct ID
3. Check for JavaScript conflicts with other libraries

### Order Not Persisting

1. Verify the `page_sections` table has a `sort_order` column
2. Check database write permissions
3. Review API response in browser DevTools

## Files Modified

| File | Changes |
|------|---------|
| `admin/api/sections.php` | NEW - API endpoint for section operations |
| `admin/page_builder.php` | Added SortableJS, toast notifications, AJAX save |
| `admin/sections.php` | Added table drag-and-drop, move buttons |
| `includes/functions.php` | Added section helper functions |
| `admin/assets/css/admin.css` | Added drag-and-drop styles |

## Future Enhancements

Potential improvements for future phases:

1. **Undo/Redo**: Add ability to undo reorder operations
2. **Multi-select**: Select multiple sections and move together
3. **Copy Between Pages**: Copy sections to different pages
4. **Section Templates**: Pre-built section configurations
5. **Revision History**: Track section order changes over time
6. **Bulk Actions**: Apply actions to multiple sections at once

## Testing Checklist

- [ ] Drag and drop works in Page Builder
- [ ] Drag and drop works in Sections table
- [ ] Order persists after page refresh
- [ ] Toast notifications appear correctly
- [ ] Loading indicator shows during save
- [ ] Up/Down buttons work as fallback
- [ ] Keyboard navigation (Alt+Up/Down) works
- [ ] Touch devices can drag sections
- [ ] API returns correct error messages
- [ ] Filter by page works correctly
- [ ] Search filters sections correctly

## Conclusion

Phase 7 successfully implements a complete drag-and-drop section reordering system with:
- Visual feedback during drag operations
- Automatic AJAX saving with debounce
- Toast notifications for user feedback
- Accessibility fallbacks with keyboard navigation
- Comprehensive API for future integrations

The implementation follows existing code patterns and integrates seamlessly with the current admin interface.
