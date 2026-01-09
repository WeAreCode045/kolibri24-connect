# Step-Based Navigation UI

## Overview
The import interface now uses a step-by-step wizard approach with Previous/Next buttons instead of vertical layout.

---

## Initial View (Step 1 Active)

```
┌────────────────────────────────────────────────────────┐
│ Step 1: Extract    →    Step 2: Select    →    Step 3: Import
│
│ ┌────────────────────────────────────────────────────┐
│ │ Step 1: Select Import Source & Extract Properties │
│ │                                                    │
│ │ ○ Download from Kolibri24 (default)              │
│ │   Download the latest property data directly...   │
│ │                                                    │
│ │ ○ Download from Remote URL                        │
│ │   Provide a custom URL to download...             │
│ │                                                    │
│ │ ○ Upload Local ZIP File                           │
│ │   Upload a ZIP file from your computer            │
│ │                                                    │
│ │ Status Messages: [empty]                          │
│ │                                                    │
│ │ Progress Bar: [empty - hidden]                    │
│ │                                                    │
│ │                 [Download & Extract Properties]   │
│ └────────────────────────────────────────────────────┘
└────────────────────────────────────────────────────────┘
```

---

## After Download (Step 2 Active)

When download completes, automatically advances to Step 2:

```
┌────────────────────────────────────────────────────────┐
│ Step 1: Extract    →    Step 2: Select    →    Step 3: Import
│
│ ┌────────────────────────────────────────────────────┐
│ │ Step 2: Select Records to Import                   │
│ │                                                    │
│ │ All properties have been merged. Select the       │
│ │ record positions you want to import via WP All    │
│ │ Import.                                            │
│ │                                                    │
│ │ ☐ Select All              Selected: 0/150        │
│ │                                                    │
│ │ ┌─────────────────────────────────────────────┐  │
│ │ │ Position 1 - ID: 12345                      │  │
│ │ │ ☑ Main Street 1, London                     │  │
│ │ │ Price: €500,000  [Image]                    │  │
│ │ ├─────────────────────────────────────────────┤  │
│ │ │ Position 2 - ID: 12346                      │  │
│ │ │ ☑ Main Road 3, Amsterdam                    │  │
│ │ │ Price: €550,000  [Image]                    │  │
│ │ ├─────────────────────────────────────────────┤  │
│ │ │ Position 3 - ID: 12347                      │  │
│ │ │ ☐ Times Square 5, London                    │  │
│ │ │ Price: €600,000  [Image]                    │  │
│ │ └─────────────────────────────────────────────┘  │
│ │                                                    │
│ │ Status Messages: [empty]                          │
│ │                                                    │
│ │      [← Back]    [Save & Continue →]              │
│ └────────────────────────────────────────────────────┘
└────────────────────────────────────────────────────────┘
```

---

## After Selection (Step 3 Active)

When user clicks "Save & Continue", automatically advances to Step 3:

```
┌────────────────────────────────────────────────────────┐
│ Step 1: Extract    →    Step 2: Select    →    Step 3: Import
│
│ ┌────────────────────────────────────────────────────┐
│ │ Step 3: Confirm & Start Import                     │
│ │                                                    │
│ │ Review your selection and start the WP All        │
│ │ Import process.                                    │
│ │                                                    │
│ │ Merged Properties Information                      │
│ │ • Total properties in file: 150                   │
│ │ • Source archive: 09-01-2026                      │
│ │ • Output path: /wp-content/uploads/...            │
│ │                                                    │
│ │ Status: Ready to import 4 records                  │
│ │                                                    │
│ │      [← Back]    [Start WP All Import →]           │
│ └────────────────────────────────────────────────────┘
└────────────────────────────────────────────────────────┘
```

---

## Confirmation Dialog (Before Import)

When user clicks "Start WP All Import":

```
┌─────────────────────────────────────────────────────┐
│ JavaScript Confirm Dialog                           │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Import properties:                                  │
│ 1. Main Street 1, London                           │
│ 2. Main Road 3, Amsterdam                          │
│ 4. Times Square 5, London                          │
│ 7. Flower Garden 112, Birmingham                   │
│                                                     │
│ Continue with import?                              │
│                                                     │
│                   [OK]    [Cancel]                 │
└─────────────────────────────────────────────────────┘
```

---

## Step 3 During Import

After confirming, Step 3 updates with progress:

```
┌────────────────────────────────────────────────────────┐
│ Step 1: Extract    →    Step 2: Select    →    Step 3: Import
│
│ ┌────────────────────────────────────────────────────┐
│ │ Step 3: Confirm & Start Import                     │
│ │                                                    │
│ │ Merged Properties Information                      │
│ │ • Total properties in file: 150                   │
│ │ • Source archive: 09-01-2026                      │
│ │ • Output path: /wp-content/uploads/...            │
│ │                                                    │
│ │ ┌──────────────────────────────────────────────┐  │
│ │ │ ✓ WP All Import triggered and processing     │  │
│ │ │   started.                                   │  │
│ │ │                                              │  │
│ │ │ Polling for completion...                    │  │
│ │ │ (checks every 2 minutes, max 30 checks)      │  │
│ │ └──────────────────────────────────────────────┘  │
│ │                                                    │
│ │      [← Back]    [Start WP All Import →] (disabled)
│ └────────────────────────────────────────────────────┘
└────────────────────────────────────────────────────────┘
```

---

## Step Navigation Features

### Forward Navigation
- **Step 1 → Step 2**: Click "Download & Extract Properties"
  - Downloads ZIP file
  - Extracts XML files
  - Merges all files into properties.xml
  - Extracts preview data with positions
  - Auto-advances to Step 2

- **Step 2 → Step 3**: Click "Save & Continue →"
  - Validates at least one record selected
  - Saves positions to WordPress option
  - Auto-advances to Step 3

- **Step 3 → Import**: Click "Start WP All Import →"
  - Shows confirmation dialog with addresses
  - Calls trigger/processing URLs
  - Starts polling for completion
  - Updates status messages

### Backward Navigation
- **Step 2 → Step 1**: Click "← Back"
  - Returns to source selection
  - Can restart download with different source

- **Step 3 → Step 2**: Click "← Back"
  - Returns to property grid
  - Can modify selections
  - Must click "Save & Continue →" again to update

---

## Step Indicator

The step indicator at the top shows:
- Current step highlighted in blue with white text
- Completed/inactive steps with gray background
- Arrow separators (→) between steps
- Always visible for user orientation

```
Step 1: Extract    →    Step 2: Select    →    Step 3: Import
[BLUE]                  [GRAY]                  [GRAY]
```

---

## Button Behavior

### Step 1
| Button | Action |
|--------|--------|
| Download & Extract Properties | Starts download workflow |

### Step 2
| Button | Action |
|--------|--------|
| ← Back | Return to Step 1 |
| Save & Continue → | Validate selection + go to Step 3 |

### Step 3
| Button | Action |
|--------|--------|
| ← Back | Return to Step 2 |
| Start WP All Import → | Show confirmation + trigger import |

---

## Responsive Design

All steps:
- Centered in card layout
- Full width on desktop (max 100%)
- Buttons centered with flexbox
- Scrolls to step indicator on navigation
- Touch-friendly button sizing

---

## JavaScript Functions

```javascript
// Navigate between steps
goToStep(stepNum)  // 1, 2, or 3

// Updates:
// - Shows/hides step containers
// - Updates step indicator badges
// - Scrolls to top of interface
// - Sets currentStep global variable
```

---

## File Changes

### PHP
- `includes/class-kolibri24-connect-admin.php` - Updated HTML structure, version 1.3.0
- CSS version bumped to 1.1.0
- JS version bumped to 1.3.0

### JavaScript
- `assets/js/admin.js` - Added `goToStep()` function, updated handlers
- Navigate on successful download/selection/confirmation

### CSS
- `assets/css/admin.css` - Added step navigation styling
- New classes: `.kolibri24-step-indicator`, `.kolibri24-step-badge`, `.kolibri24-step-active`, `.kolibri24-step-container`, `.kolibri24-step-actions`

---

## Testing Checklist

- [ ] Step 1 visible on page load
- [ ] Step 2 hidden initially
- [ ] Step 3 hidden initially
- [ ] Step indicator shows Step 1 active (blue)
- [ ] Download button downloads and extracts
- [ ] Auto-navigation to Step 2 after extraction
- [ ] Step indicator updates to Step 2 active
- [ ] Property grid displays with checkboxes
- [ ] Select checkbox and Save
- [ ] Auto-navigation to Step 3
- [ ] Step 3 shows merged file info
- [ ] Back button from Step 2 returns to Step 1
- [ ] Back button from Step 3 returns to Step 2
- [ ] Run Import shows confirmation with addresses
- [ ] Confirm dialog lists all selected positions with addresses
- [ ] Import proceeds after confirmation
- [ ] Status updates during polling
