import { usePage } from '@inertiajs/vue3';
import { FileText, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';

type NorthcloudNavItem = {
    title: string;
    href: string;
    icon: string;
};

const defaultIconMap: Record<string, LucideIcon> = {
    FileText,
};

export function useNorthcloudNavigation(extraIcons: Record<string, LucideIcon> = {}) {
    const page = usePage();
    const iconMap = { ...defaultIconMap, ...extraIcons };

    const items = computed(() => {
        const nav = (page.props.northcloud as { navigation?: NorthcloudNavItem[] })?.navigation ?? [];
        return nav.map((item) => ({
            title: item.title,
            href: item.href,
            icon: iconMap[item.icon],
        }));
    });

    return { items };
}
