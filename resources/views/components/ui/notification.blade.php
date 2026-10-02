<div
    x-data="{
        notifications: [],
        addNotification(message, type = 'dark') {
            const id = Date.now() + Math.random();
            this.notifications.push({ id, message, type });
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 4000);
           
        }
    }"
    x-init="
        window.addEventListener('notify', event => {
            addNotification(Object.entries(event.detail)[0][1]['message'],Object.entries(event.detail)[0][1]['type']);
        });
    "
    class="position-fixed bottom-0 end-0 p-3"
    style="z-index: 10000;"
>
    <template x-for="notification in notifications" :key="notification.id">
        <div 
            class="mb-2 card text-white shadow no-border-radius"
            :class="{
                'bg-success': notification.type === 'success',
                'bg-danger': notification.type === 'danger',
                'bg-warning': notification.type === 'warning',
                'bg-info': notification.type === 'info',
                'bg-dark': notification.type === 'dark'
            }"
            style="min-width: 300px;">
            <div class="card-body d-flex justify-content-between align-items-center">
                <span x-text="notification.message"></span>
                <button @click="notifications = notifications.filter(n => n.id !== notification.id)"
                        class="btn-close btn-close-white ms-2" aria-label="Close"></button>
            </div>
        </div>
    </template>
</div>