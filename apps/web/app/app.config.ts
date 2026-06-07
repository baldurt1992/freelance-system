/**
 * Tema global Nuxt UI.
 * Controles de formulario: w-full por defecto (el slot `root` del input no lo trae).
 */
export default defineAppConfig({
  ui: {
    colors: {
      primary: "brand",
      secondary: "amber",
      neutral: "slate",
    },

    dashboardSidebar: {
      slots: {
        root: "relative hidden lg:flex flex-col min-h-svh min-w-16 w-(--width) shrink-0 border-e border-default/80 bg-[linear-gradient(180deg,color-mix(in_srgb,var(--ui-color-primary-50)_88%,white)_0%,var(--ui-bg)_18%,var(--ui-bg)_100%)] shadow-[inset_-1px_0_0_0_var(--ui-border)] dark:bg-[linear-gradient(180deg,color-mix(in_srgb,var(--ui-color-primary-950)_72%,var(--ui-bg)_28%)_0%,var(--ui-bg)_24%,var(--ui-bg)_100%)]",
        header: "h-(--ui-header-height) shrink-0 flex items-center gap-1.5 px-4",
        body: "flex flex-col gap-4 flex-1 overflow-y-auto px-4 py-3",
        footer: "shrink-0 flex items-center gap-1.5 px-4 py-3",
        handle: "after:bg-gradient-to-b after:from-primary/0 after:via-primary/20 after:to-primary/0"
      }
    },

    dashboardNavbar: {
      slots: {
        root: "h-(--ui-header-height) shrink-0 flex items-center justify-between border-b border-default/80 bg-default/80 px-4 sm:px-6 gap-1.5 backdrop-blur supports-[backdrop-filter]:bg-default/70",
        title: "flex items-center gap-1.5 font-semibold text-highlighted truncate tracking-[-0.01em]"
      }
    },

    pageCard: {
      slots: {
        root: "relative flex rounded-2xl",
        container: "relative flex flex-col flex-1 lg:grid gap-x-8 gap-y-4 p-5 sm:p-6",
        title: "text-base text-pretty font-semibold tracking-[-0.01em] text-highlighted",
        description: "text-sm/6 text-toned"
      }
    },

    button: {
      slots: {
        base: "rounded-xl font-medium inline-flex items-center disabled:cursor-not-allowed aria-disabled:cursor-not-allowed disabled:opacity-75 aria-disabled:opacity-75 transition-all duration-200"
      }
    },

    formField: {
      slots: {
        root: "w-full",
        wrapper: "w-full",
        container: "relative w-full",
        label: "text-[0.72rem] font-semibold uppercase tracking-[0.12em] text-toned",
        description: "text-sm text-muted",
        error: "text-sm"
      },
    },

    input: {
      slots: {
        root: "w-full",
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] placeholder:text-dimmed/90 transition-[box-shadow,background-color,color] focus:bg-default"
      },
    },

    textarea: {
      slots: {
        root: "w-full",
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] placeholder:text-dimmed/90 transition-[box-shadow,background-color,color] focus:bg-default"
      },
    },

    inputNumber: {
      slots: {
        root: "w-full",
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] placeholder:text-dimmed/90 transition-[box-shadow,background-color,color] focus:bg-default"
      },
    },

    inputMenu: {
      slots: {
        root: "w-full",
      },
    },

    inputTags: {
      slots: {
        root: "w-full",
      },
    },

    inputDate: {
      slots: {
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] transition-[box-shadow,background-color,color] focus:bg-default",
      },
    },

    inputTime: {
      slots: {
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] transition-[box-shadow,background-color,color] focus:bg-default",
      },
    },

    select: {
      slots: {
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] transition-[box-shadow,background-color,color] focus:bg-default",
      },
    },

    selectMenu: {
      slots: {
        base: "w-full rounded-xl border-0 bg-default/88 shadow-[inset_0_0_0_1px_var(--ui-border)] transition-[box-shadow,background-color,color] focus:bg-default",
      },
    },

    dropdownMenu: {
      slots: {
        content: "min-w-36 max-h-(--reka-dropdown-menu-content-available-height) bg-default/96 shadow-[0_20px_55px_-28px_color-mix(in_srgb,var(--ui-color-primary-950)_28%,transparent)] rounded-xl ring ring-default/80 overflow-hidden data-[state=open]:animate-[scale-in_100ms_ease-out] data-[state=closed]:animate-[scale-out_100ms_ease-in] origin-(--reka-dropdown-menu-content-transform-origin) flex flex-col backdrop-blur-md",
        group: "p-1.5 isolate"
      }
    },

    pinInput: {
      slots: {
        root: "w-full",
      },
    },
  },
});
