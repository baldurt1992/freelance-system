# Dashboard Visual Identity

## Purpose

Esta guía define la identidad visual actual del dashboard de Freelance System para que diseño y frontend trabajen sobre el mismo sistema.

El tono buscado no es "template SaaS genérico". Debe sentirse:

- financiero
- sobrio
- claro
- confiable
- ligeramente editorial

La referencia mental es un **ledger moderno**: superficies limpias, acentos muy controlados, profundidad sutil y semántica cromática clara.

## Visual Direction

### Core mood

- Base clara, aireada y profesional.
- Marca principal en teal ink, no en azul eléctrico ni verde puro.
- Estados financieros diferenciados de la marca.
- Textura ambiental muy sutil, nunca fondos planos totalmente muertos.
- Bordes suaves, radios generosos y blur contenido.

### What it should not become

- No look de template default.
- No gradientes saturados tipo marketing landing.
- No purple bias.
- No dark mode dramático o neon.
- No glassmorphism exagerado.
- No sombras duras.

## Color System

### Semantic mapping

- `primary` = marca / acción principal
- `secondary` = acento cálido / atención secundaria
- `neutral` = superficies, texto, bordes
- `success` = resultado positivo / ingresos / saldo favorable
- `error` = gasto crítico / pérdida / fallo
- `warning` = pendiente / por cobrar / atención
- `info` = origen manual, estados informativos, metadata secundaria

### Brand palette

Primary alias: `brand`

```text
50  #F1FAF9
100 #D7F0EC
200 #B0E0D8
300 #7CC8BF
400 #4AAFA7
500 #2F908C
600 #237472
700 #1D5D5D
800 #1A4A4B
900 #173E3F
950 #0C2325
```

### Active aliases in UI

- `primary` -> `brand`
- `secondary` -> `amber`
- `neutral` -> `slate`

### Practical usage

- CTA principal: `primary`
- Badges de ingreso positivo: `success`
- Badges de gasto: `error`
- Pendientes por revisar o por cobrar: `warning`
- Acciones neutras, columnas, menús, secondary chrome: `neutral`

### Important rule

`primary` no debe competir con `success`.

En esta plataforma:

- `primary` = sistema / navegación / acción principal
- `success` = dinero que entra / resultado favorable

## Typography

### Base font

- `Public Sans`

### Typographic tone

- Sans limpia y administrativa.
- Títulos compactos y firmes.
- Labels pequeñas en uppercase con tracking más alto.
- Texto de soporte en tono muted, no gris demasiado bajo de contraste.

### Rules

- Headings: peso `semibold`, tracking levemente cerrado.
- Labels de formulario: uppercase, pequeñas, con tracking amplio.
- KPIs: grandes, pero no "hero numbers" exagerados.
- Descripciones: compactas y legibles, sin verse densas.

## Backgrounds And Textures

### Page background

El fondo general usa:

- gradiente lineal vertical muy suave
- radial gradient superior izquierdo con teal suave
- radial gradient superior derecho con acento cálido muy tenue

Objetivo:

- romper el fondo plano
- dar atmósfera
- mantener neutralidad

### Texture intensity

- muy baja
- casi invisible en lectura normal
- nunca debe competir con cards, tablas o inputs

## Surfaces

### Surface language

Las superficies deben sentirse:

- refinadas
- ligeramente elevadas
- suaves
- estables

### Patterns

- cards con `rounded-2xl`
- navbar con fondo translúcido suave
- sidebar con degradado leve propio
- dropdowns con blur ligero y sombra extendida suave

### Border treatment

- bordes visibles pero discretos
- usar `border-default/80` o equivalentes suaves
- evitar strokes agresivos

## Radius System

### Standard radii

- cards principales: `rounded-2xl`
- botones e inputs: `rounded-xl`
- badges: rounded suave, a veces pill
- icon containers KPI: `rounded-2xl`

### Rule

No mezclar muchos radios distintos en la misma vista sin razón. El sistema debe verse consistente y calmado.

## Shadow System

### Intent

Las sombras no son decorativas; solo deben separar capas.

### Characteristics

- blandas
- extendidas
- de baja opacidad
- más notorias en dropdowns
- muy sutiles en cards

### Avoid

- sombras negras cortas y duras
- glow saturado
- elevaciones muy teatrales

## Motion

### Motion style

- rápida pero suave
- estable
- sin rebotes
- sin cambios bruscos de transform

### Current direction

- transitions entre `200ms` y `300ms`
- `ease-out`
- hover lift mínimo
- sombras que aparecen progresivamente

### Interaction rules

- hovers deben sentirse refinados, no saltar
- cards KPI: micro-lift sutil
- links de pendientes: micro-lift de 1px aprox, no más
- menus/dropdowns: apertura breve y limpia

### Avoid

- spotlight agresivo
- parallax
- transformaciones grandes
- animaciones que llamen más la atención que los datos

## Component Guidance

### Sidebar

- Debe sentirse como panel de navegación estructural, no como bloque oscuro aislado.
- Usa gradiente muy tenue desde teal claro al fondo neutral.
- Estados activos con acento `primary`, no con rellenos muy pesados.
- Hover sutil.

### Navbar

- Fondo translúcido suave.
- Línea inferior clara.
- Debe separar navegación de contenido sin parecer header corporativo pesado.

### Toolbar shells

- Deben vivir dentro de una cápsula visual propia.
- Fondo translúcido neutral.
- Borde suave.
- Espaciado corto y eficiente.

### Forms

- Inputs full width.
- Superficie de input ligeramente elevada sobre el fondo.
- Labels pequeñas, técnicas y consistentes.
- Formularios agrupados dentro de cards o bloques claros.

### Tables

- Header con contraste leve respecto al body.
- Bordes nítidos pero suaves.
- Estado vacío centrado y discreto.
- Los badges no deben robar peso visual a la fila.

### KPI cards

- Fondo claro, sutilmente elevado.
- Icon container con color semántico, pero borde suave.
- El dato es el protagonista, no el icono.

### Status badges

- `subtle` por defecto
- color semántico correcto
- nunca sobresaturados

## Layout Rules

### Width system

Usar dos carriles principales:

- `PageContentNarrow` -> `max-w-4xl`
- `PageContentWide` -> `max-w-7xl`

### When to use each

- Narrow:
  - formularios de una sola columna
  - detalles compactos
  - vistas CRUD simples

- Wide:
  - layouts con summary lateral
  - dashboards
  - pantallas con varias cards o densidad mayor

## Reusable UI Wrappers Already In The App

Estos wrappers ya existen y deben respetarse:

- `PageContentNarrow`
- `PageContentWide`
- `PageToolbarShell`
- `PageSectionCard`
- `PageStateCard`
- `TableEmptyState`

Si diseño propone una nueva variante, debe justificar qué problema real resuelve y por qué no encaja en estos bloques.

## Figma Guidance

Para que otro agente o diseñador trabaje bien esto en Figma:

### Build these styles first

- color styles semánticos
- text styles para:
  - page title
  - section title
  - field label
  - muted helper text
  - KPI value
- effect styles:
  - card shadow soft
  - dropdown shadow
- radius tokens:
  - xl
  - 2xl

### Figma structure recommendation

Crear foundation pages para:

1. Color roles
2. Typography
3. Spacing and radius
4. Surface styles
5. Motion notes
6. Component examples

### Mock first

Las primeras pantallas ideales para validar la identidad son:

1. Dashboard home
2. Finanzas list
3. Finance entry form
4. Quote detail

## Do / Don't

### Do

- Mantener el tono financiero sobrio.
- Usar la marca como guía, no como saturación.
- Diferenciar claramente acciones del sistema vs estados monetarios.
- Trabajar profundidad con capas suaves.
- Dar prioridad a legibilidad y jerarquía de datos.

### Don't

- No convertirlo en un marketing UI.
- No usar colores demasiado vibrantes en todo.
- No confundir marca con success.
- No usar sombras pesadas.
- No meter texturas ruidosas.
- No abusar de glass o blur.

## Implementation Reference

La fuente de verdad visual actual está en:

- `apps/web/app/assets/css/main.css`
- `apps/web/app/app.config.ts`

Si una propuesta de diseño contradice esos archivos, debe decidirse explícitamente si:

- es exploración
- o es cambio real del sistema visual

No asumir que una exploración visual cambia automáticamente el theme implementado.
