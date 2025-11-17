import '../../vendor/masmerise/livewire-toaster/resources/js';
const eventSource = new EventSource("/.well-known/mercure?topic=test");
eventSource.onmessage = function (event) {
    console.log("New message:", event.data);
};
