import { EventEmitter } from 'events';

export class DashboardEvents extends EventEmitter {
    public emit<T extends string>(type: T, payload?: any): boolean {
        return super.emit(type, payload);
    }

    public on<T extends string>(type: T, handler: (payload: any) => void): this {
        return super.on(type, handler);
    }

    public off<T extends string>(type: T, handler: (payload: any) => void): this {
        return super.off(type, handler);
    }

    public removeAllListeners<T extends string>(type?: T): this {
        return super.removeAllListeners(type);
    }

    public removeListener<T extends string>(type: T, handler: (payload: any) => void): this {
        return super.removeListener(type, handler);
    }
}

export const Events = new DashboardEvents(); 