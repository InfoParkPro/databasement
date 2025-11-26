<div>
    <div class="mx-auto max-w-4xl">
        <x-header title="{{ __('Appearance') }}" subtitle="{{ __('Update the appearance settings for your account') }}" size="text-2xl" separator class="mb-6" />

        <x-card>
            <div x-data="{
                theme: localStorage.getItem('theme') || 'system',
                setTheme(value) {
                    this.theme = value;
                    localStorage.setItem('theme', value);
                    if (value === 'system') {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
                    } else {
                        document.documentElement.setAttribute('data-theme', value);
                    }
                }
            }" class="flex flex-wrap gap-2">
                <x-button
                    :label="__('Light')"
                    icon="o-sun"
                    @click="setTheme('light')"
                    x-bind:class="theme === 'light' ? 'btn-primary' : 'btn-outline'"
                />
                <x-button
                    :label="__('Dark')"
                    icon="o-moon"
                    @click="setTheme('dark')"
                    x-bind:class="theme === 'dark' ? 'btn-primary' : 'btn-outline'"
                />
                <x-button
                    :label="__('System')"
                    icon="o-computer-desktop"
                    @click="setTheme('system')"
                    x-bind:class="theme === 'system' ? 'btn-primary' : 'btn-outline'"
                />
            </div>
        </x-card>
    </div>
</div>
