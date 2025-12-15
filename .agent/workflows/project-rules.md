---
description: Project coding standards and conventions for CitroPak DMS
---

# CitroPak DMS - Project Rules

## Backend (Laravel)

### Service Classes
- **Every module MUST have a Service class** in `app/Services/`
- Controllers should be thin - delegate business logic to services
- Example structure:
  ```
  app/Services/
  ├── OrderService.php
  ├── StockService.php
  ├── InvoiceService.php
  └── ReportService.php
  ```

### Service Class Template
```php
<?php

namespace App\Services;

class ExampleService
{
    public function getAll()
    {
        // Business logic here
    }

    public function create(array $data)
    {
        // Validation and creation logic
    }

    public function update(int $id, array $data)
    {
        // Update logic
    }

    public function delete(int $id)
    {
        // Delete logic
    }
}
```

### Controller Usage
```php
public function __construct(private ExampleService $service) {}

public function index()
{
    return Inertia::render('Example/Index', [
        'items' => $this->service->getAll()
    ]);
}
```

---

## Frontend (Vue.js)

### Reusable Components
- **Use components from `resources/js/Components/Form/`** for all form inputs
- **NO duplicate input code** - always use the shared components
- Available components:
  - `TextInput.vue` - Text, email, password inputs
  - `SelectInput.vue` - Dropdown selects
  - `TextareaInput.vue` - Multiline text
  - `Checkbox.vue` - Checkboxes
  - `DateInput.vue` - Date pickers

### Component Usage Example
```vue
<script setup>
import TextInput from '@/Components/Form/TextInput.vue';
import SelectInput from '@/Components/Form/SelectInput.vue';
</script>

<template>
    <TextInput
        v-model="form.name"
        label="Name"
        :error="form.errors.name"
        required
    />
    
    <SelectInput
        v-model="form.category_id"
        label="Category"
        :options="categories"
        :error="form.errors.category_id"
    />
</template>
```

---

## File Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Service | PascalCase + Service | `OrderService.php` |
| Controller | PascalCase + Controller | `OrderController.php` |
| Vue Page | PascalCase | `OrderIndex.vue` |
| Vue Component | PascalCase | `TextInput.vue` |
| Migration | snake_case with timestamp | `create_orders_table.php` |
