Form Text Input With Affixes Select
===================================

This component is a wrapper around the default form text input that adds support for select affixes, i.e. the ability to display a `FormSelect` either at the beginning or at the end of the text input.

## Props

### `affixValue`

Default affix value for the component.

### `prefix`

Markup to be inserted at the beginning of the input, in form of an object containing key/value pairs for the `FormSelect`.

### `suffix`

Markup to be appended at the end of the input, in form of an object containing key/value pairs for the `FormSelect`.

### `onSelectChange`

This handler will be called whenever the values of the `FormSelect` are changed.

### `noWrap`

A flag that prevents the prefix and suffix from wrapping when the component is displayed on small viewports. This basically disables the corresponding breakpoint.
