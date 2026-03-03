<template>
  <span
    class="app-tooltip"
    :class="[`pos-${position}`]"
    tabindex="0"
    :aria-describedby="tooltipId"
  >
    <slot>
      <span class="tip-icon" aria-hidden="true">?</span>
    </slot>

    <span
      class="tooltip-content"
      role="tooltip"
      :id="tooltipId"
    >
      {{ text }}
    </span>
  </span>
</template>

<script>
export default {
  name: "AppTooltip",
  props: {
    text: { type: String, required: true },
    position: { type: String, default: "top" },
  },
  computed: {
    tooltipId() {
      return `tt-${this._uid}`;
    },
  },
};
</script>

<style lang="scss" scoped>
.app-tooltip {
  position: relative;
  display: inline-flex;
  align-items: center;
  cursor: help;
  outline: none;

  .tooltip-content {
    position: absolute;

    max-width: 260px;
    width: max-content;

    padding: 6px 8px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.3;

    white-space: normal;
    word-break: break-word;
    text-align: left;

    /* INFO THEME */
    background: linear-gradient(
      var(--bs-info),
      color-mix(in srgb, var(--bs-info) 75%, black)
    );

    color: var(--bs-white);
    border: 1px solid color-mix(in srgb, var(--bs-info) 60%, black);
    border-radius: 3px;

    box-shadow:
      inset 0 1px 0 rgba(255, 255, 255, 0.15),
      0 2px 6px rgba(0, 0, 0, 0.25);

    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.12s ease, transform 0.12s ease;

    z-index: 100000;
  }

  /* Arrow */
  .tooltip-content::after {
    content: "";
    position: absolute;
    width: 8px;
    height: 8px;

    background: color-mix(in srgb, var(--bs-info) 75%, black);
    border-left: 1px solid color-mix(in srgb, var(--bs-info) 60%, black);
    border-top: 1px solid color-mix(in srgb, var(--bs-info) 60%, black);

    transform: rotate(45deg);
  }

  &:hover .tooltip-content,
  &:focus-visible .tooltip-content {
    opacity: 1;
    visibility: visible;
  }

  /* TOP */
  &.pos-top .tooltip-content {
    left: 50%;
    bottom: calc(100% + 8px);
    transform: translateX(-50%) translateY(4px);
  }
  &.pos-top:hover .tooltip-content,
  &.pos-top:focus-visible .tooltip-content {
    transform: translateX(-50%) translateY(0);
  }
  &.pos-top .tooltip-content::after {
    left: 50%;
    bottom: -5px;
    transform: translateX(-50%) rotate(225deg);
  }

  /* RIGHT */
  &.pos-right .tooltip-content {
    left: calc(100% + 8px);
    top: 50%;
    transform: translateY(-50%) translateX(-4px);
  }
  &.pos-right:hover .tooltip-content,
  &.pos-right:focus-visible .tooltip-content {
    transform: translateY(-50%) translateX(0);
  }
  &.pos-right .tooltip-content::after {
    left: -5px;
    top: 50%;
    transform: translateY(-50%) rotate(45deg);
  }

  /* BOTTOM */
  &.pos-bottom .tooltip-content {
    left: 50%;
    top: calc(100% + 8px);
    transform: translateX(-50%) translateY(-4px);
  }
  &.pos-bottom:hover .tooltip-content,
  &.pos-bottom:focus-visible .tooltip-content {
    transform: translateX(-50%) translateY(0);
  }
  &.pos-bottom .tooltip-content::after {
    left: 50%;
    top: -5px;
    transform: translateX(-50%) rotate(45deg);
  }
}

/* Info-style question icon */
.tip-icon {
  width: 16px;
  height: 16px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  font-size: 11px;
  font-weight: 700;

  border-radius: 50%;

  background: color-mix(in srgb, var(--bs-info) 15%, var(--bs-body-bg));
  color: var(--bs-info);
  border: 1px solid color-mix(in srgb, var(--bs-info) 40%, var(--bs-body-bg));

  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.7),
    0 1px 0 rgba(0, 0, 0, 0.05);

  user-select: none;
}
</style>