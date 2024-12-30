Below is a **final, detailed `README.md`** suited for **Cursor AI** integration. It provides a clear overview of your **Feature-First**, **WordPress-based** project design, references your new **`ARCHITECTURE.md`**, and outlines development workflows, data storage strategies, and testing procedures.

---

# Athlete Dashboard

A **React/TypeScript-powered** WordPress child theme designed to help athletes track and analyze workouts, goals, and progress. This project follows a **Feature-First** architecture, relying on **WordPress user meta** for storing user information (Profile, Training Persona, Equipment/Environment) and a **custom post type** for workouts.

---

## **Project Overview**

1. **Feature-First Architecture**  
   - Each feature is self-contained, including its own logic, UI components, styling, and documentation.  
   - Communication happens via an **event-driven** system, ensuring features remain decoupled.

2. **Hybrid WordPress + React**  
   - **React/TypeScript** forms the interactive frontend (modals, dashboards).  
   - **WordPress PHP** provides the backend, using user meta for profile data and a custom post type (`workout`) for repeated entries.

3. **Key Objectives**  
   - **Profile Management**: Collect user details (e.g., Age, Gender, Height, Weight, Injuries).  
   - **Training Persona**: Store preferences (experience level, workout frequency) in user meta.  
   - **Equipment/Environment**: Let users specify gym equipment or environment constraints.  
   - **AI Workout Generator**: Merge user data to generate or iterate workouts, storing them as `workout` posts.  
   - **Optional Analytics**: In the future, gather insights from the stored workouts to track progress and guide further AI-driven suggestions.

---

## **Architecture**

This project uses a **Feature-First** architecture described in detail in **[`ARCHITECTURE.md`](./ARCHITECTURE.md)**. Key points include:

- **Encapsulation**: Each feature manages its own assets and logic.  
- **Minimal Shared Files**: Only essential shared resources (like an event bus) live outside feature folders.  
- **Event-Driven**: Features emit and listen for typed events to coordinate actions between React components and PHP hooks.  
- **WordPress Data Model**: 
  - **User meta** for storing Profile, Persona, Equipment/Environment.  
  - **Custom Post Type** (`workout`) for logging and organizing workouts.

Please refer to **[`ARCHITECTURE.md`](./ARCHITECTURE.md)** for a comprehensive architectural breakdown.

---

## **Directory Structure**

A typical layout (simplified):

```plaintext
athlete-dashboard-child/
├── dashboard/                  # Core WP+React framework
│   ├── core/                   # PHP core classes
│   ├── components/             # (Optional) shared React components
│   ├── events.ts               # Event bus for the entire dashboard
│   ├── styles/                 # Shared style tokens (optional)
│   └── templates/              # WP templates
├── features/                   # Modular feature folders
│   ├── profile/                # Basic user profile data in user meta
│   ├── training-persona/       # Persona data (level, frequency) in user meta
│   ├── environment/            # Equipment/environment data in user meta
│   └── ai-workout-generator/   # Generates or iterates workouts, stored as CPT
├── assets/                     # Static assets and build outputs
│   ├── dist/                   # Production builds (Vite output)
│   └── src/                    # Source assets (if shared)
└── tests/                      # Unit and integration tests
```

Each **feature** has its own:

- **`components/`** (React + TSX files)  
- **`assets/`** (SCSS, JS/TS services)  
- **`events.ts`** (feature-specific events)  
- **Feature interface implementation** (register/init for the dashboard)  
- **`README.md`** (feature-level documentation)

---

## **Key Features**

1. **Profile** (`features/profile/`)  
   - Stores user info (Name, Age, Gender, Injuries) in WordPress user meta.  
   - Emits `profile:updated` event on data changes.

2. **Training Persona** (`features/training-persona/`)  
   - Captures training level, desired workout duration, frequency.  
   - Also saved in user meta.  
   - Emits `training-persona:updated` on changes.

3. **Equipment/Environment** (`features/environment/`)  
   - Lists available equipment or environment constraints.  
   - Stored in user meta as `_user_equipment` or similar.  
   - Emits `environment:updated` after updates.

4. **AI Workout Generator** (`features/ai-workout-generator/`)  
   - Combines data from Profile, Persona, and Equipment to create or iterate workouts.  
   - Stores generated workouts as a **custom post type** (`workout`), using WordPress post meta for sets, reps, etc.  
   - Emits `ai-workout:generated` on successful creation.

---

## **Installation & Setup**

1. **Clone the Repository**  
   ```bash
   git clone <repo-url> athlete-dashboard-child
   cd athlete-dashboard-child
   ```

2. **Install Dependencies**  
   - **Node.js & npm** (for frontend build)  
   - **Composer** (for PHP dependencies)
   ```bash
   npm install
   composer install
   ```

3. **Development Build**  
   ```bash
   npm run dev
   ```
   - Runs Vite’s development server with hot module replacement (HMR).

4. **Production Build**  
   ```bash
   npm run build
   ```
   - Outputs hashed, minified assets to `assets/dist/`.

5. **Activate the Child Theme**  
   - Copy the theme folder to `wp-content/themes/`.  
   - Activate it in **WordPress Admin** (`Appearance > Themes`).

---

## **How to Develop Features**

1. **Create a Feature Folder**  
   - `features/<feature-name>/`  
   - Inside, create subfolders: `components/`, `assets/js/`, `assets/scss/`, and `events.ts`.  
2. **Implement `FeatureInterface`**  
   - A typical `FeatureInterface` includes methods like `register()` and `init()`.  
   - Ensure the feature is recognized by the main dashboard loader.  
3. **Define Events**  
   - Add typed events to your `events.ts`.  
   - Use the shared `Events` bus in `dashboard/events.ts` to emit or listen.  
4. **Enqueue Assets**  
   - In a PHP file (e.g., `functions.php` or a dedicated loader), enqueue the built script and style for your feature.  
   - WordPress automatically looks for your compiled files in `assets/dist/`.

For a **step-by-step** example, see any existing feature’s `README.md`.

---

## **Workflow Example: Generating an AI Workout**

1. **Profile & Persona Data**  
   - A user updates their basic profile (e.g., Age, Injuries) and training persona (Level, Frequency) in user meta.  
2. **Equipment**  
   - The user specifies available equipment in `environment` feature.  
3. **AI Generator Request**  
   - The user clicks “Generate Workout.” The `ai-workout-generator` feature collects user meta, constructs a prompt, and calls the AI.  
4. **New Workout Stored**  
   - The returned workout is saved as a `workout` custom post, with sets/reps in post meta.  
5. **Final Editing & Logging**  
   - The user edits sets or reps and logs the workout for the day.  
   - An event like `workout:logged` is emitted.

---

## **Testing**

1. **JavaScript/TypeScript Tests**  
   ```bash
   npm run test
   ```
   - Uses a test runner (e.g., Jest) for unit tests.  
2. **PHP Tests**  
   ```bash
   composer test
   ```
   - Runs PHPUnit tests. Filter specific features:  
     ```bash
     composer test -- --filter=<FeatureTest>
     ```
3. **Integration Tests**  
   - Validate event flows between multiple features (e.g., a profile update triggering a persona update).  
4. **Browser Dev Tools**  
   - Check console logs if `Events.enableDebug()` is activated in `dashboard/events.ts`.

---

## **Deployment**

1. **Build Assets**  
   ```bash
   npm run build
   ```
   - Minifies and hashes JS/CSS into `assets/dist/`.
2. **Deploy Theme**  
   - Upload the `athlete-dashboard-child` theme folder (including `dist/` assets) to your WordPress install.  
   - Activate via **WordPress Admin**.
3. **Verify**  
   - Check if all features (Profile, Persona, Environment, etc.) are accessible, and the AI generator is functioning with proper network calls.

---

## **Troubleshooting**

1. **Assets Not Loading**  
   - Ensure `npm run build` was run before uploading.  
   - Verify script enqueue paths in your loader or `functions.php`.
2. **No Events Detected**  
   - Confirm event strings match exactly in `Events.on(...)` and `Events.emit(...)`.  
   - Check if `Events.enableDebug()` logs appear in the console.
3. **Data Not Updating**  
   - Confirm you’re using `update_user_meta()` and reading from the correct meta key.  
   - Make sure your custom post type is registered and `wp_insert_post` or `update_post_meta` calls are correct.

---

## **Contributing**

1. **Fork & Branch**  
   ```bash
   git checkout -b feature/<your-feature>
   ```
2. **Add Your Feature**  
   - Follow **Feature-First** guidelines: new folder, self-contained assets, event definitions.  
   - Write or update tests.  
3. **Pull Request**  
   - Make sure everything passes `npm run test` and `composer test`.  
   - Submit a PR with a clear summary of your changes.

---

## **License**

GNU General Public License v2 (or later). Refer to [LICENSE](LICENSE) for details.

---

## **Additional References**

- **[`ARCHITECTURE.md`](./ARCHITECTURE.md)**: Detailed technical breakdown of the Feature-First and event-driven approach.  
- **[WordPress Developer Docs](https://developer.wordpress.org/)**: For custom post types, user meta, and theme development best practices.  
- **React & TypeScript**: For frontend building, strongly typed components, and event-driven UI logic.

---

**This `README.md`** provides the starting point for **Cursor AI** to scaffold and automate coding tasks. If you have further questions or need to expand features, consult the **feature-specific READMEs** or reach out to the development team. 

Enjoy building your **Athlete Dashboard**!