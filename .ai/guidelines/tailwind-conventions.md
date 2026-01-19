## Tailwind Conventions

These guidelines should be respected:

### Use size class when width and height are identical

Use `size-*` instead of `w-* h-*`.

Example:

Replace `w-4 h-4` with `size-4` or `w-5 h-5` with `size-5`.


### Always support dark mode

Always try to support a dark mode by adding text and background color definitions for dark mode to. Use the class-based approach `dark:...`.


### Always support the WCAG 2.0

We try to support the WCAG 2.0 with at least AA standard. So try to keep enough contrast for the used text color on background color.

