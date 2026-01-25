export interface Connection {
    id: number;
    type: string;
    version: null | string;
    name: string;
    host: string;
    port: number;
    username: string;
    password?: string;
    database: string;
    is_production_stage: boolean;
    last_tested_at: null | string;
    last_tested_at_label: string;
    is_connectable: boolean;
    last_test_result: string;
}
