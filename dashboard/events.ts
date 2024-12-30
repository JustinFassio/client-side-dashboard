type EventCallback = (data: any) => void;

class EventBus {
    private listeners: Map<string, EventCallback[]> = new Map();
    private debug: boolean = false;

    public enableDebug(): void {
        this.debug = true;
    }

    public on(event: string, callback: EventCallback): void {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event)?.push(callback);
    }

    public emit(event: string, data: any): void {
        if (this.debug) {
            console.log(`Event emitted: ${event}`, data);
        }
        this.listeners.get(event)?.forEach(callback => callback(data));
    }

    public off(event: string, callback: EventCallback): void {
        const callbacks = this.listeners.get(event) || [];
        const index = callbacks.indexOf(callback);
        if (index > -1) {
            callbacks.splice(index, 1);
        }
    }
}

export const Events = new EventBus(); 