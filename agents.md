# AGENTS.md - Vioniko Live Webinar Redesign Project

## 1. Project Overview
This repository contains the source code for the **Vioniko Live Webinar Page** redesign. The goal is to modernize the legacy 3-column layout into a futuristic, cybernetic glassmorphic interface (Dark Blue & Neon Cyan glow) that matches the target visual specifications:
- Desktop Design Reference: [`new_design.jpg`](file:///E:/Subroto/webiner/new_design.jpg)
- Mobile Design Reference: [`new_design_mobile.jpg`](file:///E:/Subroto/webiner/new_design_mobile.jpg)
- Legacy Output Screenshot: [`old_screensort.jpg`](file:///E:/Subroto/webiner/old_screensort.jpg)
- Raw HTML Render Dump: [`output.html`](file:///E:/Subroto/webiner/output.html)

---

## 2. Core Rule & Working Files
> [!IMPORTANT]
> **CRITICAL RULE**: NEVER edit [`live_webinar.php`](file:///E:/Subroto/webiner/live_webinar.php) directly! It must remain untouched as the pristine baseline.
> Always make changes and work on the copy: [`live_webinar_redesign.php`](file:///E:/Subroto/webiner/live_webinar_redesign.php).

### File Map
| File | Role / Purpose | Status |
| :--- | :--- | :--- |
| [`live_webinar.php`](file:///E:/Subroto/webiner/live_webinar.php) | Original production PHP file | **FROZEN / READ-ONLY** |
| [`live_webinar_redesign.php`](file:///E:/Subroto/webiner/live_webinar_redesign.php) | Redesigned production PHP file | **ACTIVE WORKING FILE** |
| [`css/redesign.css`](file:///E:/Subroto/webiner/css/redesign.css) | Custom master stylesheet with neon theme & responsive rules | **ACTIVE** |
| [`preview.html`](file:///E:/Subroto/webiner/preview.html) | Standalone HTML preview with mock data for instant browser testing | **ACTIVE** |
| [`output.html`](file:///E:/Subroto/webiner/output.html) | Raw rendered HTML dump from live webinar | Reference |
| `css/style.css`, `css/main.css`, `css/poll.css` | Legacy stylesheets | Retained for fallback |

---

## 3. Visual Design System

### Colors & Glassmorphism
- **Base Background**: `#030a1a` (Deep Cyber Midnight Blue)
- **Card Background**: `rgba(3, 16, 40, 0.78)` with `backdrop-filter: blur(20px)`
- **Neon Cyan Borders**: `rgba(0, 195, 255, 0.45)` to `#00d2ff`
- **Neon Glow Shadow**: `0 0 25px rgba(0, 170, 255, 0.28), inset 0 0 20px rgba(0, 150, 255, 0.08)`
- **Gold CTA Button Gradient**: `linear-gradient(180deg, #ffc837 0%, #f39c12 100%)` with dark bold text `#1a1500`
- **Window Titlebar Dots**: Red (`#ff5f56`), Yellow (`#ffbd2e`), Green (`#27c93f`)

### Typography
- **Headings & Badges**: `'Montserrat', sans-serif` (uppercase, letter-spaced)
- **Body & Inputs**: `'Inter', system-ui, sans-serif`

---

## 4. Layout Architecture

### A. Top Navigation Header (`.header`)
- **Left**: Glowing Globe icon + "VIONIKO" bold text + separator `|` + tagline "CONOCIMIENTO | ESTRATEGIA | RESULTADOS" (tagline hidden on mobile).
- **Right**: Pulsing cyan live dot + "WEBINAR EN VIVO".

### B. Video Window Card (`.video-window-card`)
- **macOS Window Header**: 3 colored dots + title `"PowerPoint Slide Show - [Presentacion24x7Breve]"`.
- **Video Area**: 16:9 responsive frame supporting both HTML5 automated `<video>` and Google Hangout `<iframe>`.
- **Title Sub-bar**: Centered webinar title flanked by glowing cyan gradient divider lines.
- **Purchase Button Container**: Preserves `<div class="webinar_purchase"></div>` for dynamic timed purchase CTA.

### C. Bottom Control Center Panel (Desktop vs Mobile)
- **Desktop (`>= 992px`)**:
  - Displays as a unified horizontal 3-column glassmorphic card.
  - **Module 1**: Audio Configuration (Speaker icon, mute button `<i id="mute"></i>`, custom neon cyan range slider `<input type="range" id="volume-bar">`).
  - **Module 2**: Attendees List (Users icon, title `"PARTICIPANTES PRESENTES"`, white card with attendee names and custom scrollbar).
  - **Module 3**: Q&A Form (Envelope icon, title `"ENVÍA TU PREGUNTA"`, subtitle, two side-by-side inputs for Name & Email, Message textarea, and gold `"ENVIAR MENSAJE"` button).
- **Mobile (`< 992px`)**:
  - The 3 modules seamlessly transform into **3 Stacked Collapsible Accordion Cards** (`.control-module` with `.accordion-header`, `.accordion-chevron`, and `.accordion-body`).
  - Tapping each card's header expands/collapses the section with a smooth animated chevron rotation.
  - Participants are formatted as inline pills / comma-separated list.
  - Form inputs stack vertically.

### D. Collapsible Chat Drawer & Floating Triggers
In the original design, the chat permanently consumed 25% of the screen width. In the redesign:
- **Desktop Trigger**: A sleek docked floating tab on the right screen edge (`.chat-edge-tab`) with chat icon, vertical text "Chat", and `<` chevron.
- **Mobile Trigger**: A glowing circular floating action button (`.chat-mobile-fab`) fixed at bottom-right.
- **Chat Drawer**: Slides in from the right edge (`.chat-drawer.open`), containing `#chat-section` with all original PHP / WebSocket / polling chat functionality intact.

---

## 5. Preserved Element IDs and JavaScript Contract

The following IDs, classes, and function names **must never be altered** because existing scripts, AJAX polling, and backend handlers rely on them:

| Selector / ID | Purpose | JS Handlers / Dependencies |
| :--- | :--- | :--- |
| `#video` | HTML5 video player | `video.play()`, `video.volume`, `video.muted`, iOS check |
| `#player_holder` | Hangout / HTML5 video wrapper | `jQuery('#player_holder').html(...)` |
| `#hangout_video` | Google Hangout iframe | Handled via polling |
| `#video_status` | Hidden input for hangout state | `jQuery('#video_status').val()` |
| `#mute` | Audio mute toggle icon | `muteButton.addEventListener("click")` |
| `#volume-bar` | Audio range slider | `volumeBar.addEventListener("change")` & `"input"` |
| `#user_exists` | Hidden attendee count | Active attendee tracking |
| `.user-count` | Attendee count badge | Socket / AJAX attendee count |
| `.user-list` | Attendee list wrapper | Attendee listing |
| `.User_list` | Attendee names container | Populated via PHP dummy users |
| `.Online_User_list` | Live online user list | WebSocket / polling injection |
| `#enquiryForm` | Q&A contact form | `submitEnquiry()` |
| `#webinar_token` | Hidden webinar token | Form submission |
| `#webinar_admin_email` | Hidden admin email | Form submission |
| `#webid` | Hidden webinar title | Form submission |
| `#vector-smart-object-copy-13` | Name input | `submitEnquiry()` validation |
| `#vector-smart-object-copy-17-1` | Email input | `submitEnquiry()` validation |
| `#vector-smart-object-10` | Message textarea | `submitEnquiry()` validation |
| `#loader_enquiry` | Enquiry loading GIF | Show/hide on form submission |
| `.succes-text` | Success feedback message | Shown after `mail_sent` response |
| `.webinar_purchase` | Delayed purchase CTA | Populated after configured timeout |
| `#chat-section` | Native webinar chat container | `checkChatStatus()`, WebSocket, socket.io |
| `#chat-banned-msg` | Chat banned / offline notice | Polling status display |
| `#access-btn`, `#overlay` | Video ready access modal | Gatekeeper before autoplay |

---

## 6. How to Test & Preview

1. **Standalone Browser Testing (No PHP required)**:
   - Double-click or open [`preview.html`](file:///E:/Subroto/webiner/preview.html) in Chrome or Edge.
   - Resize browser width above/below `992px` to verify the responsive shift between the Desktop 3-Column Control Panel and Mobile Accordion Cards.
   - Test the right edge "Chat <" tab on Desktop and circular "Chat" FAB on Mobile to verify drawer slide-out.
   - Test volume slider and mute button.
   - Test the Q&A form simulation.

2. **PHP Production Deployment**:
   - Deploy [`live_webinar_redesign.php`](file:///E:/Subroto/webiner/live_webinar_redesign.php) and [`css/redesign.css`](file:///E:/Subroto/webiner/css/redesign.css) to the server.
   - When ready to replace production, backup [`live_webinar.php`](file:///E:/Subroto/webiner/live_webinar.php) on the server and swap with `live_webinar_redesign.php`.
