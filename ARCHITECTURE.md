Below is a **final version** of the `ARCHITECTURE.md` file, designed for your **Cursor AI** workflow. It synthesizes all prior discussions into a concise, **Feature-First**, **event-driven** design using **WordPress user meta** for profile data and a **custom post type** for workouts. Feel free to modify any part as needed for your specific environment.

---

# Athlete Dashboard Architecture

## **Overview**

The **Athlete Dashboard** is a **Feature-First** WordPress child theme that integrates **React/TypeScript** for the frontend and **WordPress PHP** for the backend. This system stores all user-centric data (e.g., Profile, Training Persona, Equipment/Environment) in WordPress user meta and logs workouts as a custom post type (`workout`). An **event-driven** design decouples features, ensuring each module remains self-contained and scalable.

---

## **Core Principles**

1. **Feature Encapsulation**  
   - Each feature is self-contained with its UI, logic, assets, and data.  
   - No unintended dependencies or shared state between features.

2. **Minimal Shared Files**  
   - Only essential shared assets (e.g., an event bus, global style tokens if absolutely needed).  
   - All other resources reside within each feature’s folder.

3. **Event-Driven Communication**  
   - Features communicate via a centralized event system (`events.ts` in `dashboard/`), emitting and listening to typed events.  
   - WordPress hooks (`do_action`, `add_action`) mirror this concept on the PHP side.

4. **Scalability & Maintainability**  
   - Each feature can be independently developed, tested, or replaced.  
   - A fully modular design makes the system easier to evolve over time.

---

## **Feature-First Structure**

### **Why Feature-First?**
- **Clarity**: Each feature directory holds everything for that feature (React components, SCSS, PHP integration, documentation).  
- **Rapid Development**: New features can be added without disturbing others.  
- **Easy Collaboration**: Developers quickly understand each feature’s scope and dependencies.

### **Typical Feature Layout**
```plaintext
features/
└── training-persona/
    ├── components/
    │   └── TrainingPersonaModal.tsx
    ├── assets/
    │   ├── js/
    │   │   └── trainingPersonaService.ts
    │   └── scss/
    │       └── trainingPersona.scss
    ├── events.ts
    ├── TrainingPersonaFeature.ts
    └── README.md
```

---

## **Data Storage**

### **1. User Meta for Profile Data**

- **Profile**, **Training Persona**, and **Equipment/Environment** details are stored in **WordPress user meta**.  
- This keeps the system simple and avoids creating new DB tables:
  - `_profile_age`, `_profile_gender`, `_profile_injuries`, `_training_persona_level`, `_user_equipment`, etc.
- **Access**:  
  - PHP: `update_user_meta($user_id, '_profile_age', $new_age);`  
  - React/TS: via WP’s REST API or a custom AJAX endpoint that retrieves or updates user meta.

### **2. Custom Post Type for Workouts**

- **Workouts** are created as posts of type `workout` to track multiple sessions per user.  
- **Post Meta** stores workout details like `_workout_exercises`, `_workout_program`, `_workout_ai_prompt`, etc.
- **Advantages**:  
  - Built-in WordPress admin pages for listing, editing, or searching workouts.  
  - Easy to group workouts into “Programs” via a taxonomy (e.g., `program`) or a meta field like `_program_id`.

---

## **Event-Driven Design**

1. **Front-End Events**: Centralized in `dashboard/events.ts`, using a strongly typed system for React/TS.  
   ```typescript
   Events.on('profile:updated', (data) => { /* ... */ });
   Events.emit('ai-workout:generated', { workoutData });
   ```
2. **Back-End Hooks**: Use `do_action` and `add_action` in PHP for integration with WordPress.  
   ```php
   do_action('workout_completed', $workout_id);
   add_action('workout_completed', function($workout_id) { /* ... */ });
   ```
3. **Feature Independence**: Each feature manages its own events (`events.ts`), referencing the shared event bus to communicate externally.

---

## **AI Integration**

- **Purpose**: Generate new workouts or iterate existing ones using user meta (Profile, Persona, Equipment).  
- **Flow**:  
  1. **Gather Data** from `user_meta` (training level, injuries, equipment, etc.).  
  2. **Construct AI Prompt** in a service (e.g., `generatorService.ts`).  
  3. **AI Response** is parsed, and a new `workout` post is created or updated.  
  4. **Final Display** in a React component (e.g., `AiWorkoutModal.tsx`), allowing the user to edit sets/reps before saving.

---

## **Directory Structure**

```plaintext
athlete-dashboard-child/
├── dashboard/                  # Core dashboard framework
│   ├── core/                   # Core PHP classes
│   ├── components/             # Shared React components (only if necessary)
│   ├── events.ts               # Event bus for the entire dashboard
│   ├── styles/                 # Shared style tokens (optional, minimal usage)
│   └── templates/              # Dashboard PHP templates
├── features/                   # Modular features
│   ├── profile/                # Profile feature (user meta)
│   ├── training-persona/       # Training persona feature (user meta)
│   ├── environment/            # Equipment/Environment feature (user meta)
│   └── ai-workout-generator/   # AI generator feature (writes to workout CPT)
├── assets/                     # Compiled assets
│   ├── dist/                   # Production-ready assets
│   └── src/                    # Source files (if any shared scripts exist)
└── tests/                      # Unit and integration tests
```

---

## **Asset Management**

- **Vite** is used for bundling in development and production.  
  - **`npm run dev`**: Hot reloading with React.  
  - **`npm run build`**: Minified, hashed assets go to `assets/dist/`.
- **WordPress Enqueue**:  
  ```php
  function enqueue_workout_assets() {
    wp_enqueue_script(
      'workout-js',
      get_theme_file_uri('/assets/dist/js/workout.abc123.js'),
      ['wp-element'],
      null,
      true
    );
  }
  add_action('wp_enqueue_scripts', 'enqueue_workout_assets');
  ```

---

## **Testing & Debugging**

1. **Unit Tests**:  
   - **JavaScript/TypeScript**: `npm run test`.  
   - **PHP**: `composer test -- --filter=<FeatureTest>`.
2. **Integration Tests**:  
   - Validate event-driven flows between features.  
   - Confirm workouts are correctly saved as `workout` posts.
3. **Debug Mode**:  
   ```typescript
   import { Events } from '@dashboard/events';
   Events.enableDebug();
   // Logs all emitted events to the console
   ```

---

## **Next Steps**

1. **Finalize Profile & Persona**  
   - Make sure user meta fields and forms align with your data needs.  
   - Emit `profile:updated` or `training-persona:updated` events as appropriate.
2. **Implement Equipment/Environment**  
   - Store `_user_equipment` or `_user_environment` in user meta, with the option to expand if needed.
3. **Build the AI Workout Generator**  
   - Merge data from user meta to form AI prompts.  
   - Write or update `workout` posts with the returned exercise data.
4. **Add Optional Analytics**  
   - Tally workout data from the `workout` CPT to display progress or track usage over time.

---

## **Conclusion**

This **Feature-First** WordPress architecture marries **React/TypeScript** with a **minimal** reliance on shared files. By storing user data in `user_meta` and workouts as a custom post type, you:

- **Stay fully in WordPress’s ecosystem**  
- **Encourage modularity** via self-contained features  
- **Enable easy expansion** of AI-driven functionalities and advanced analytics

Keep each feature isolated, rely on an event bus for communication, and let WordPress handle the data storage. This approach ensures a **scalable, maintainable**, and **developer-friendly** foundation for your athlete-focused dashboard and AI-driven workout generation.

---

**Need more details?**  
- Refer to each feature’s own `README.md` for implementation specifics.  
- Check the main `README.md` for setup commands, build instructions, and testing guides.  