
export interface Connection {
    id: number;
    type: string;
    name: string;
    host: string;
    port: number;
    username: string;
    password?: string;
    database: string;
    is_production_stage: boolean;
}
