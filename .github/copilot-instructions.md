# Onboarder - AI Coding Agent Instructions

## Project Overview
Employee onboarding management system built with **Symfony 7.3** and **MariaDB**. Core purpose: automate and track tasks during employee onboarding with configurable workflows, email notifications, and role-based task assignments.

## Architecture & Domain Model

### General
- **Code COnventions** Aller Code soll Clean Code Richtlinien folgen. 
- **Comments** Kommentare sollen auf Deutsch sein, Methoden und Klassen sollen englische Namen haben.
- **Infrastructure** Alles soll unter docker also docker compose laufen

### Core Entities
- **Onboarding**: Employee master data (name, entry date, role, team, manager, buddy)
- **OnboardingType**: Template configurations for different employee roles/departments
- **TaskBlock**: Logical groupings (IT, HR, etc.) containing related tasks
- **Task**: Individual onboarding steps with due dates, assignees, and email triggers
- **Role**: Reusable assignee definitions with email addresses
- **BaseType**: Shared task template that cascades changes to derived OnboardingTypes

### Key Business Rules
1. **OnboardingType immutability**: Once assigned to an Onboarding, the type cannot be changed
2. **BaseType inheritance**: Changes to BaseTypes automatically propagate to all derived OnboardingTypes
3. **Task dependency chains**: Tasks can depend on completion of other tasks
4. **External completion**: Tasks must be completable via email links without login
5. **No user roles**: All authenticated users have identical permissions (admin functions for everyone)

## Email System Architecture

### Email Trigger Types
- **Immediate**: Send when onboarding is created
- **Fixed date**: Send on specific calendar date
- **Relative date**: Send X days before/after entry date
- **Manual**: No automatic sending
- **Reminder**: Secondary email with separate timing rules

### Template Variables
Support personalized emails with variables like `{{firstName}}`, `{{lastName}}`, `{{entryDate}}`, `{{manager}}`, `{{buddy}}`. Each task has its own HTML template with completion links for external users.

## Data Design Patterns

### Flexible Due Dates
Tasks support both:
- **Fixed dates**: Specific calendar dates
- **Relative dates**: X days before/after employee entry date

### Task Assignment Flexibility
- Direct email addresses
- Role-based assignments (roles have predefined email addresses)
- Per-onboarding task customization (add/remove/modify individual tasks)

## Technical Specifications

### Framework & Database
- **Backend**: Symfony 7.3 (PHP framework)
- **Database**: MariaDB
- **Interface**: Web-based (no desktop client)
- **Authentication**: Username/password (no role differentiation)

### SMTP Configuration
Admin-configurable email server settings:
- Host, Port, Username, Password
- Certificate validation toggle

### No Export Requirements
System is view-only - no Excel/PDF export functionality needed.

## Development Priorities

### MVP Feature Set
1. **Onboarding CRUD**: Create/manage employee onboardings with master data
2. **Type Templates**: Admin area for OnboardingTypes and TaskBlocks
3. **Task Management**: Configure tasks with timing, assignments, dependencies
4. **Email System**: Template management with variable substitution
5. **Monitoring Views**: Individual and global task overviews with filtering

### Key Views Required
- **Individual Dashboard**: All tasks for one employee (status, due dates, assignees)
- **Global Overview**: All tasks across all onboardings (workload analysis)
- **Admin Panels**: OnboardingType, TaskBlock, Role, and Email template management

## Code Organization Guidance

When implementing, consider Symfony's structure:
- **Entities**: Core domain models in `src/Entity/`
- **Controllers**: Separate admin and user-facing controllers
- **Services**: Email service, task generation logic, dependency resolution
- **Forms**: Type-specific form builders for complex task configurations
- **Templates**: Twig templates for both web UI and email HTML

Focus on the inheritance relationship between BaseType → OnboardingType → Onboarding → Tasks for proper cascade behavior.

