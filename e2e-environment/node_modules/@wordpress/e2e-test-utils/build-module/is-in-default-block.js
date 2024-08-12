/**
 * Checks if the block that is focused is the default block.
 *
 * @return {Promise} Promise resolving with a boolean indicating if the focused block is the default block.
 */
export function isInDefaultBlock() {
  return page.evaluate(function () {
    var activeElement = document.activeElement; // activeElement may be null in that case we should return false

    if (!activeElement) {
      return false;
    }

    var closestElementWithDataTpe = activeElement.closest('[data-type]');

    if (!closestElementWithDataTpe) {
      return false;
    }

    var activeBlockName = closestElementWithDataTpe.getAttribute('data-type');
    var defaultBlockName = window.wp.blocks.getDefaultBlockName();
    return activeBlockName === defaultBlockName;
  });
}
//# sourceMappingURL=is-in-default-block.js.map