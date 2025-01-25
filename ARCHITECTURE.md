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

2. **WordPress Integration**
   - Uses WordPress's built-in React (`wp-element`)
   - Leverages WordPress hooks and filters
   - Follows WordPress coding standards

3. **Event-Driven Communication**  
   - Features communicate via WordPress hooks (`do_action`, `add_action`)
   - React components use event system for frontend communication
   - Typed events ensure type safety across features

4. **Scalability & Maintainability**  
   - Each feature can be independently developed, tested, or replaced.  
   - A fully modular design makes the system easier to evolve over time.

---

## **Feature-First Structure**

### **Why Feature-First?**
- **Clarity**: Each feature directory holds everything for that feature (React components, SCSS, PHP integration, documentation).  
- **Rapid Development**: New features can be added without disturbing others.  
- **Easy Collaboration**: Developers quickly understand each feature's scope and dependencies.

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
    ├── index.ts
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
  - React/TS: via WP REST API or AJAX endpoints

### **2. Custom Post Type for Workouts**

- **Workouts** are created as posts of type `workout` to track multiple sessions per user.  
- **Post Meta** stores workout details like `_workout_exercises`, `_workout_program`, `_workout_ai_prompt`, etc.
- **Advantages**:  
  - Built-in WordPress admin pages for listing, editing, or searching workouts.  
  - Easy to group workouts into "Programs" via taxonomy or meta field.

---

## **Build System**

1. **Development**
   - Uses `@wordpress/scripts` for modern development workflow
   - Hot reloading for React components
   - TypeScript compilation
   - SCSS processing

2. **Production**
   - Optimized builds with WordPress scripts
   - Asset versioning and cache busting
   - Proper WordPress integration

---

## **Directory Structure**

```plaintext
athlete-dashboard-child/
├── dashboard/                  # Core dashboard framework
│   ├── core/                   # Core PHP classes
│   ├── components/             # Shared React components
│   └── templates/              # Dashboard PHP templates
├── features/                   # Modular features
│   ├── profile/                # Profile feature (user meta)
│   ├── training-persona/       # Training persona feature (user meta)
│   ├── environment/            # Equipment/Environment feature (user meta)
│   └── ai-workout-generator/   # AI generator feature (writes to workout CPT)
├── assets/                     # Static assets
│   ├── build/                  # Production-ready assets
│   └── src/                    # Source files
└── tests/                      # Unit and integration tests
```

---

## **Testing & Debugging**

1. **Unit Tests**:  
   - JavaScript/TypeScript: `npm run test`
   - PHP: WordPress testing framework
2. **Integration Tests**:  
   - Validate feature interactions
   - Test WordPress hooks and filters
3. **Debug Mode**:  
   ```php
   define('WP_DEBUG', true);
   ```

---

## **Next Steps**

1. **Finalize Profile & Persona**  
   - Implement user meta fields and forms
   - Set up WordPress hooks for updates
2. **Implement Equipment/Environment**  
   - Store equipment data in user meta
3. **Build the AI Workout Generator**  
   - Create workout custom post type
   - Implement AI integration
4. **Add Analytics**  
   - Track workout progress
   - Monitor system usage

---

## **Conclusion**

This **Feature-First** WordPress architecture marries **React/TypeScript** with WordPress's robust backend. By storing user data in `user_meta` and workouts as a custom post type, you:

- **Stay fully in WordPress's ecosystem**  
- **Encourage modularity** via self-contained features  
- **Enable easy expansion** of AI-driven functionalities

Keep each feature isolated, rely on WordPress hooks for communication, and let WordPress handle the data storage. This approach ensures a **scalable, maintainable**, and **developer-friendly** foundation for your athlete-focused dashboard.

---

**Need more details?**  
- Refer to each feature's own `README.md` for implementation specifics.  
- Check the main `README.md` for setup commands, build instructions, and testing guides.  