
<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';

import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';

import { Button } from '@/components/ui/button';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';

import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

import { getInitials } from '@/composables/useInitials';

import type {
    BreadcrumbItem as BreadcrumbItemType,
    NavItem,
    SharedData,
} from '@/types';

import { Link, usePage } from '@inertiajs/vue3';

import {
    LayoutGrid,
    Menu,
} from 'lucide-vue-next';

import { computed } from 'vue';

// PROPIEDADES
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// USUARIO AUTENTICADO
const page = usePage<SharedData>();

const auth = computed(() => page.props.auth);

// RUTA ACTUAL
const isCurrentRoute = (url: string) => {
    return page.url.split('?')[0] === url;
};

const activeItemStyles = computed(() => (url: string) =>
    isCurrentRoute(url)
        ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
        : ''
);

// NAVEGACIÓN
const mainNavItems: NavItem[] = [
    {
        title: 'Panel Principal',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div
                class="mx-auto flex h-16 items-center px-4 md:max-w-7xl"
            >
                <!-- MENÚ MÓVIL -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>

                        <SheetContent
                            side="left"
                            class="w-[300px] p-6"
                        >
                            <SheetTitle class="sr-only">
                                Menú de navegación
                            </SheetTitle>

                            <SheetHeader
                                class="flex justify-start text-left"
                            >
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                                <span class="font-bold">
                                    SistemaSnack
                                </span>
                            </SheetHeader>

                            <nav class="mt-6 space-y-2">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                    :class="activeItemStyles(item.href)"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="h-5 w-5"
                                    />
                                    {{ item.title }}
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>

                <!-- LOGOTIPO -->
                <Link
                    href="/dashboard"
                    class="flex items-center gap-x-2"
                >
                    <AppLogo class="hidden h-6 xl:block" />
                    <span class="font-bold xl:hidden">
                        SistemaSnack
                    </span>
                </Link>

                <!-- MENÚ DE ESCRITORIO -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu
                        class="ml-10 flex h-full items-stretch"
                    >
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="item in mainNavItems"
                                :key="item.title"
                                class="relative flex h-full items-center"
                            >
                                <Link :href="item.href">
                                    <NavigationMenuLink
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            activeItemStyles(item.href),
                                            'h-9 cursor-pointer px-3',
                                        ]"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ item.title }}
                                    </NavigationMenuLink>
                                </Link>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- PERFIL DEL USUARIO -->
                <div
                    v-if="auth?.user"
                    class="ml-auto flex items-center space-x-2"
                >
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />

                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{
                                            getInitials(
                                                auth.user.name
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent
                            align="end"
                            class="w-56"
                        >
                            <UserMenuContent
                                :user="auth.user"
                            />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <!-- RUTA DE NAVEGACIÓN -->
        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumb>
                    <BreadcrumbList>
                        <template
                            v-for="(item, index) in props.breadcrumbs"
                            :key="index"
                        >
                            <BreadcrumbItem>
                                <BreadcrumbPage
                                    v-if="index === props.breadcrumbs.length - 1"
                                >
                                    {{ item.title }}
                                </BreadcrumbPage>

                                <BreadcrumbLink
                                    v-else
                                    :href="item.href"
                                >
                                    {{ item.title }}
                                </BreadcrumbLink>
                            </BreadcrumbItem>

                            <BreadcrumbSeparator
                                v-if="index !== props.breadcrumbs.length - 1"
                            />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </div>
</template>
