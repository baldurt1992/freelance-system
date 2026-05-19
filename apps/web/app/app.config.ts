/**
 * Tema global Nuxt UI.
 * Controles de formulario: w-full por defecto (el slot `root` del input no lo trae).
 */
export default defineAppConfig({
  ui: {
    colors: {
      primary: "green",
      neutral: "zinc",
    },

    formField: {
      slots: {
        root: "w-full",
        wrapper: "w-full",
        container: "relative w-full",
      },
    },

    input: {
      slots: {
        root: "w-full",
      },
    },

    textarea: {
      slots: {
        root: "w-full",
      },
    },

    inputNumber: {
      slots: {
        root: "w-full",
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
        base: "w-full",
      },
    },

    inputTime: {
      slots: {
        base: "w-full",
      },
    },

    select: {
      slots: {
        base: "w-full",
      },
    },

    selectMenu: {
      slots: {
        base: "w-full",
      },
    },

    pinInput: {
      slots: {
        root: "w-full",
      },
    },
  },
});
