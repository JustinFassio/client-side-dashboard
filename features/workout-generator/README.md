# AI Workout Generator

## Overview
The **AI Workout Generator** dynamically generates personalized workout plans by aggregating user-specific data from other features, such as **Profile**, **Equipment**, and **Training Preferences**. These plans are further customized based on inputs from the Workout Generator widgets, including workout length, type, and environment. Generated workouts can be saved to the Workout Builder system for future use.

---

## Key Features
1. **Data Aggregation**:
   - Pulls fitness level, health conditions, goals, and preferences from the **Profile Feature**.
   - Integrates available equipment from the **Equipment Feature**.
   - Uses training preferences (e.g., workout types, durations) from the **Training Preferences Feature**.
   - Merges this data with user inputs in the AI Workout Generator.

2. **Dynamic Template Creation**:
   - Automatically generates a workout plan template based on aggregated data.
   - Populates exercises filtered by equipment, target muscles, and fitness level.
   - Customizes instructions and settings (e.g., warmups, cooldowns, rest periods).

3. **Integration with Workout Builder**:
   - Uses a backend API to save generated workout plans for future use.
   - Ensures workouts are accessible in the Workout Builder system.

4. **Interactive Widgets**:
   - **PreferencesWidget**: Allows users to customize preferences and settings for workouts.
   - **WorkoutPlanWidget**: Displays the dynamically generated workout plan.
   - **WorkoutHistoryWidget**: Tracks previously generated workouts.

---

## Data Flow
1. **Data Aggregation**:
   - The `DataAggregator` collects and merges data from the Profile, Equipment, and Training Preferences features, along with inputs from the Workout Generator widgets.

2. **Template Generation**:
   - The `generateWorkoutTemplate` function dynamically builds the workout plan based on aggregated data and user settings.

3. **API Interaction**:
   - The `WorkoutBuilderService` sends the generated workout to the backend via the `/workout-builder/save` endpoint.

4. **Display in UI**:
   - The generated workout is rendered in the `WorkoutPlanWidget` for user review and saved to the Workout Builder upon confirmation.

---

## Components
### **WorkoutGenerator**
The main container for the feature, which aggregates data, generates workouts, and displays the resulting plan and history.

### **Widgets**
1. **PreferencesWidget**
   - Captures user preferences, including fitness level, workout duration, and preferred exercises.
   - Includes settings for warmups, cooldowns, and rest periods.

2. **WorkoutPlanWidget**
   - Displays the dynamically generated workout plan, including exercises and instructions.
   - Allows users to save the workout.

3. **WorkoutHistoryWidget**
   - Tracks previously generated workouts.
   - Displays metadata, such as duration, target goals, and creation date.

---

## Context
The `WorkoutProvider` manages state and provides actions for:
- **`generateWorkout`**: Generates a new workout plan using aggregated data.
- **`saveWorkout`**: Saves a workout plan to the backend.
- **`loadHistory`**: Fetches the user's workout history.
- **`updatePreferences`**: Updates user preferences dynamically.
- **`updateSettings`**: Adjusts workout settings, such as warmup inclusion and max exercises.

---

## Services
### **WorkoutBuilderService**
Handles API interactions:
1. **`generateWorkout`**:
   - Generates a workout plan based on user preferences and settings.
   - **Endpoint**: `/workout-builder/generate`
   - **Method**: POST
   - **Payload**:
     ```json
     {
       "preferences": { ... },
       "settings": { ... }
     }
     ```

2. **`saveWorkout`**:
   - Saves the generated workout to the backend.
   - **Endpoint**: `/workout-builder/save`
   - **Method**: POST
   - **Payload**:
     ```json
     {
       "id": "workout_123456",
       "name": "Advanced Strength Workout",
       "duration": 45,
       ...
     }
     ```

3. **`getWorkoutHistory`**:
   - Fetches the user's workout history.
   - **Endpoint**: `/workout-builder/history/{userId}`
   - **Method**: GET

---

## API Endpoints
### `/workout-builder/generate`
- **Method**: POST
- **Description**: Dynamically generates a workout plan using user preferences and settings.
- **Request Body**:
  ```json
  {
    "preferences": {
      "fitnessLevel": "beginner",
      "availableEquipment": ["dumbbells", "bands"],
      "preferredDuration": 30,
      "targetMuscleGroups": ["legs", "back"],
      "healthConditions": []
    },
    "settings": {
      "includeWarmup": true,
      "includeCooldown": true,
      "preferredExerciseTypes": ["strength", "cardio"],
      "maxExercisesPerWorkout": 5,
      "restBetweenExercises": 60
    }
  }
  ```

### `/workout-builder/save`
- **Method**: POST
- **Description**: Saves the generated workout to the backend.
- **Request Body**:
  ```json
  {
    "id": "workout_123456",
    "name": "Beginner Strength Workout",
    "duration": 30,
    "exercises": [
      { "id": "1", "name": "Push-Ups", ... },
      ...
    ]
  }
  ```

### `/workout-builder/history/{userId}`
- **Method**: GET
- **Description**: Fetches previously saved workouts.

---

## Example Workflow
1. **Aggregate Data**:
   - Use `DataAggregator` to collect user-specific data from features like Profile and Equipment.
2. **Generate Workout Template**:
   - Dynamically create a workout using `generateWorkoutTemplate`.
3. **Save Workout**:
   - Use `WorkoutBuilderService.saveWorkout()` to persist the generated plan.
4. **Display in UI**:
   - Render the workout in `WorkoutPlanWidget` and allow users to save or modify it.

---

## Future Enhancements
1. **Integration with AI Models**:
   - Use AI to dynamically adjust exercises based on real-time user feedback.
2. **Advanced Progress Tracking**:
   - Add analytics for tracking user performance and workout adherence.
3. **Sharing Features**:
   - Enable users to share their workouts with others. 