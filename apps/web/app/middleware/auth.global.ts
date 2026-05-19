export default defineNuxtRouteMiddleware(async (to) => {
  const auth = useAuthStore();

  if (!auth.initialized) {
    await auth.initialize();
  }

  const isLoginRoute = to.path === "/login";

  if (isLoginRoute) {
    if (auth.isAuthenticated) {
      return navigateTo("/");
    }
    return;
  }

  if (to.meta.guest) {
    return;
  }

  if (!auth.isAuthenticated) {
    return navigateTo("/login");
  }
});
