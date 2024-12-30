Below is a **final and highly detailed** `ROADMAP.md` file for **Cursor AI**. It provides a structured plan for feature development, illustrating how to phase in core functionality while maintaining a **Feature-First**, **event-driven**, and **WordPress-centric** approach.

---

# Athlete Dashboard Roadmap

This **ROADMAP.md** outlines the development phases for the **Athlete Dashboard** child theme. Each phase builds upon the previous one, ensuring a smooth progression from basic user profile features to an AI-driven workout generator and optional analytics.

---

## **Phase 1: Basic Profile Feature**

### **Objectives**
1. **Core User Data**: Capture crucial user fields such as Name, Email, Phone, Age, Gender, Height, Weight, Injuries, and Medical Clearance.  
2. **WordPress User Meta**: Store these fields in `user_meta`, leveraging WP’s existing data structure for simplicity.

### **Implementation Steps**
1. **Create `features/profile/`**  
   - Include a **ProfileForm** or similar UI component for capturing profile details.  
   - Define events in `events.ts` (e.g., `profile:updated`) to broadcast changes.
2. **Integrate with WordPress**  
   - Use `add_user_meta()` and `update_user_meta()` in PHP or via REST/AJAX to update these fields.  
   - Optionally create a custom admin page or a front-end form for editing profile data.
3. **Testing**  
   - Ensure each profile field is saved correctly in `wp_usermeta`.  
   - Confirm that the `profile:updated` event fires after saves.

### **Outcomes**
- A **foundational** user profile system that other features can reference.
- Minimal database setup, keeping the solution simple.

---

## **Phase 2: Training Persona Feature**

### **Objectives**
1. **Persona Fields**: Collect user preferences such as **Training Level** (beginner, intermediate, advanced), **Workout Duration**, and **Workout Frequency**.  
2. **Extend WordPress User Meta**: Store these as `_training_level`, `_training_duration`, `_training_frequency`.

### **Implementation Steps**
1. **Create `features/training-persona/`**  
   - Add a **TrainingPersonaForm** with the relevant fields.  
   - Define and emit `training-persona:updated` when saved.
2. **Connect to Profile**  
   - If necessary, reference basic profile data (age, injuries) to adjust persona defaults or recommended ranges.
3. **Testing**  
   - Verify persona data updates in user meta.  
   - Check that `training-persona:updated` events are emitted correctly.

### **Outcomes**
- A user’s **training preferences** can now inform future workout plans.
- A consistent pattern for storing feature-specific data in user meta.

---

## **Phase 3: Equipment/Environment Feature**

### **Objectives**
1. **Equipment & Constraints**: Let the user list available equipment (e.g., dumbbells, barbells) and/or environment constraints (home gym, limited space).  
2. **Continue Using User Meta**: Store data as `_user_equipment` or `_user_environment`, potentially as a serialized array or JSON string if multiple items are needed.

### **Implementation Steps**
1. **Create `features/environment/`** (or `features/equipment/`)  
   - Add a form or interface (e.g., `EnvironmentForm.tsx`) that collects and displays user equipment.  
   - Emit `environment:updated` upon save.
2. **Data Model**  
   - Decide how to store multiple equipment entries (array vs. taxonomy vs. multiple meta keys).  
   - Keep it simple (e.g., one meta key `_user_equipment` with JSON).
3. **Testing**  
   - Confirm changes persist in user meta.  
   - Validate that the equipment list is retrievable for other features (like the AI generator).

### **Outcomes**
- The system now understands the user’s environment, enabling more **targeted workout generation** based on available gear.

---

## **Phase 4: AI Workout Generator**

### **Objectives**
1. **Central Feature**: Merge data from Profile, Training Persona, and Equipment to produce customized workout plans via AI.  
2. **New Workouts as a Custom Post Type**: Each AI-generated session is stored as a `workout` CPT in WordPress (with sets, reps, and details in post meta).

### **Implementation Steps**
1. **Create `features/ai-workout-generator/`**  
   - Add a `generatorService.ts` (or similar) to gather user data (from user meta) and construct an AI prompt.  
   - Implement `AiWorkoutFeature.ts` with `FeatureInterface`, listening for `ai-workout:request-generation`.
2. **Register the `workout` CPT**  
   - In a PHP file (e.g., `dashboard/core/register-post-types.php`), call `register_post_type('workout', [...])`.  
   - Use `post meta` to store `_workout_exercises`, `_workout_duration`, `_program_id` if applicable.
3. **Data Flow**  
   - **Front End**: A user triggers “Generate Workout.” The feature fetches user meta (profile, persona, equipment), then calls the AI.  
   - **AI Response**: The new workout is saved as a `workout` post, with relevant data in post meta.  
   - **UI/Editing**: A React component (`AiWorkoutModal.tsx`) displays the workout. The user can edit sets or reps before finalizing.  
   - **Events**: 
     - `ai-workout:generated` on successful creation.  
     - `workout:save` or `workout:logged` once the user confirms.
4. **Programs** (Optional)  
   - Either define a custom taxonomy `program` or a `_program_id` meta field to group workouts.
5. **Testing**  
   - Generate a brand-new workout from scratch (Profile + Persona + Equipment).  
   - “Iterate” an existing workout (e.g., add more volume, reduce time).
   - Confirm data is saved correctly in the `workout` CPT and that events flow as expected.

### **Outcomes**
- An **intelligent, AI-driven** approach to creating and iterating workouts.  
- Workouts are now easily trackable as posts in WordPress, with built-in listing and editing capabilities.

---

## **Phase 5: Analytics & Benchmark Tests (Optional)**

### **Objectives**
1. **Performance Tracking**: Provide users with insights into their progress (volume, frequency, PRs).  
2. **Benchmark Tests**: Offer 1RM tests, timed runs, or other metrics that the AI can factor into future workouts.

### **Implementation Steps**
1. **Create `features/analytics/`** or `features/benchmark-tests/`  
   - Pull data from `workout` CPT to analyze sets, reps, progress over time.  
   - Optionally store results from benchmark tests in post meta or another feature-specific meta field (`_benchmark_1rm`, `_benchmark_time`, etc.).
2. **UI**  
   - Add React components for graphs, tables, or progress bars.  
   - Potentially display improvements or highlight plateaus that the AI generator can address in new workouts.
3. **Testing**  
   - Confirm analytics logic accurately reads `workout` data.  
   - Ensure benchmark updates flow back into the AI generator if you choose to integrate them.

### **Outcomes**
- The Dashboard evolves into a **comprehensive fitness platform**, using real data to personalize and improve user workouts.

---

## **Implementation Timeline**

While each phase is distinct, they can overlap in development if your team is large enough. For a smaller team or a single developer:

1. **Phase 1** (Profile) \[1–2 weeks\]  
   - Basic user data fields + storage in user meta.  
   - Simple form for editing user info.
2. **Phase 2** (Training Persona) \[1–2 weeks\]  
   - Additional form for user’s workout preferences.  
   - Integration with Profile if needed (injuries, age).
3. **Phase 3** (Equipment) \[1–2 weeks\]  
   - UI for listing equipment or environment constraints.  
   - Store in user meta, test retrieval logic.
4. **Phase 4** (AI Workout Generator) \[2–4 weeks\]  
   - Larger chunk of work: hooking up AI calls, building the CPT for workouts, user editing in React.  
   - Potentially add “Programs” grouping.
5. **Phase 5** (Analytics/Benchmarks) \[2–4 weeks, optional\]  
   - Data visualization, potential advanced AI adaptation.  
   - Benchmark integration and historical tracking.

Exact timelines depend on complexity and team size.

---

## **Maintaining the Feature-First Approach**

Throughout each phase, **keep the modules independent**:

- **Profile** doesn’t rely on direct references to **Training Persona** or **Equipment**; it simply raises events like `profile:updated`.  
- **AI Workout Generator** fetches needed data via user meta lookups or by listening to events, rather than referencing each feature’s internals.  
- Any shared logic (event bus, minimal style tokens) lives in the `dashboard/` folder, used only to glue features together, not to store feature-specific resources.

---

## **Summary**

1. **Phased Development** ensures that you implement each feature in a logical sequence, starting with user basics and culminating in AI-powered workouts and analytics.  
2. **WordPress Simplicity**: By storing user data in user meta and workouts as a custom post type, you avoid complex database migrations or custom queries.  
3. **Extensible Event-Driven Architecture**: Features remain decoupled, testable, and maintainable, paving the way for future expansions such as advanced analytics or additional AI functionality.

This **ROADMAP.md** aims to guide the **Cursor AI** and your team through a step-by-step plan, ensuring each phase builds on the success of the last. If you have further questions or need to add new phases, simply expand this roadmap with additional features or improvements. 

**Good luck, and enjoy building the Athlete Dashboard!**