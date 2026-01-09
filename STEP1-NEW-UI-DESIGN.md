# Step 1 UI Redesign: Default Kolibri24 with Change Source Button

## New Interface Design

### Default View (Step 1 Loaded)

```
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Select Import Source & Extract Properties           │
│                                                             │
│ ☁️  Download from Kolibri24                                │
│     Download the latest property data directly from        │
│     the Kolibri24 API.                                    │
│                                                             │
│              [Change Source]                              │
│                                                             │
│ Status Messages: [empty]                                   │
│                                                             │
│ Progress Bar: [hidden]                                     │
│                                                             │
│      [Download & Extract Properties]                      │
└─────────────────────────────────────────────────────────────┘
```

### After Clicking "Change Source" Button

```
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Select Import Source & Extract Properties           │
│                                                             │
│ ☁️  Download from Kolibri24                                │
│     Download the latest property data directly from        │
│     the Kolibri24 API.                                    │
│                                                             │
│              [Change Source]                              │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐│
│ │ Select a different import source:                       ││
│ │                                                         ││
│ │ ○ ☁️  Download from Kolibri24                          ││
│ │    Download the latest property data directly...      ││
│ │                                                         ││
│ │ ○ 🔗 Download from Remote URL                         ││
│ │    Provide a custom URL to download a ZIP file.       ││
│ │                                                         ││
│ │ ○ ⬆️  Upload Local ZIP File                            ││
│ │    Upload a ZIP file from your computer.              ││
│ │    [File input - hidden]                              ││
│ │                                                         ││
│ │ ○ 📦 Use Previous Archive                              ││
│ │    Load a previously downloaded archive.              ││
│ │    [Archive dropdown - hidden]                        ││
│ │                                                         ││
│ │    [Confirm Selection]  [Cancel]                       ││
│ └─────────────────────────────────────────────────────────┘│
│                                                             │
│ Status Messages: [empty]                                   │
│                                                             │
│      [Download & Extract Properties]                      │
└─────────────────────────────────────────────────────────────┘
```

### After Selecting Different Source (e.g., Remote URL)

When user selects "Download from Remote URL" and clicks "Confirm Selection":

```
┌─────────────────────────────────────────────────────────────┐
│ Step 1: Select Import Source & Extract Properties           │
│                                                             │
│ 🔗 Download from Remote URL                                │
│    Provide a custom URL to download a ZIP file.            │
│                                                             │
│              [Change Source]                              │
│                                                             │
│ ┌───────────────────────────────────────────────────────┐ │
│ │ https://example.com/properties.zip                   │ │
│ └───────────────────────────────────────────────────────┘ │
│                                                             │
│ Status Messages: [empty]                                   │
│                                                             │
│      [Download & Extract Properties]                      │
└─────────────────────────────────────────────────────────────┘
```

---

## User Experience Flow

### Initial State
1. **Default Display**: Shows Kolibri24 as the selected source with icon and description
2. **Clean UI**: Only one "Download & Extract Properties" button visible
3. **Easy Access**: "Change Source" button clearly visible for alternative options

### Changing Source
1. **Click "Change Source"** → Source selector panel slides down
2. **Select Option** → Choose from 4 sources with descriptions
3. **Click "Confirm Selection"** → Panel slides up, display updates to show selected source
4. **Input Fields** → Only relevant input field is shown (URL, file, archive)
5. **Ready to Download** → User can click main download button

### Advantages
✅ **Cleaner Default UI** - Less cluttered, easier to understand
✅ **Focus on Primary Action** - Kolibri24 download is prominent
✅ **Easy Source Switching** - Click to change, confirm to apply
✅ **Responsive Design** - Panel slides smoothly
✅ **Mobile Friendly** - Smaller initial footprint
✅ **Accessibility** - Clear labels and descriptions

---

## Implementation Details

### HTML Structure
- **Default Display**: `.kolibri24-default-source` div shows current source with icon
- **Change Button**: Opens/closes source selector panel
- **Source Selector**: Contains 4 radio buttons with descriptions
- **Confirm/Cancel**: Buttons to apply or discard changes
- **Hidden Input**: `<input name="kolibri24-import-source">` stores current value

### JavaScript Behavior
- **On Page Load**: Source selector is hidden by default
- **Change Source Click**: Slides down source selector panel
- **Radio Button Change**: Updates field visibility (URL, file, archive)
- **Confirm Selection**: 
  - Updates hidden input value
  - Updates display with new source info
  - Slides up source selector panel
  - Calls handleSourceChange to show relevant inputs
- **Cancel**: Just slides up without changes

### CSS Classes
- `.kolibri24-default-source` - Main display area
- `.kolibri24-source-selection` - Hidden source selector container
- `.kolibri24-collapsible` - Input fields (URL, file, archive)
- `.is-open` - Shows collapsible field

---

## Visual Changes from Previous Design

| Aspect | Before | After |
|--------|--------|-------|
| **Source Display** | 4 radio buttons all visible | Default source shown, button to change |
| **Input Fields** | All visible by default | Hidden until source is selected |
| **Space Usage** | Takes up more vertical space | More compact, cleaner |
| **Primary Focus** | Multiple options equally prominent | Kolibri24 is primary action |
| **Switching Sources** | Just click radio button | Click "Change Source", select, confirm |

---

## Browser Compatibility
- Uses jQuery slideDown/slideUp for animations
- CSS flexbox for layout
- Standard HTML5 inputs
- Compatible with all modern browsers
- Graceful degradation if JavaScript disabled

---

## Accessibility
- Radio buttons remain keyboard accessible
- Labels properly associated with inputs
- Icon + text descriptions for clarity
- "Change Source" button has clear purpose
- Confirm/Cancel actions are clear

---

## Testing Checklist

- [ ] Page loads with Kolibri24 as default source
- [ ] Default display shows cloud icon, label, and description
- [ ] "Change Source" button visible and clickable
- [ ] Clicking "Change Source" slides down selector panel
- [ ] All 4 source options visible in selector panel
- [ ] Selecting each option shows correct input fields
- [ ] "Confirm Selection" updates display correctly
- [ ] "Cancel" closes panel without changing source
- [ ] Hidden input stores correct source value
- [ ] Download button works with each source
- [ ] Remote URL validation works
- [ ] File upload validation works
- [ ] Archive selection works
- [ ] Step advances to Step 2 after download
- [ ] Back button from Step 2 returns to Step 1 with selected source preserved
