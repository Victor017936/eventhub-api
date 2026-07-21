import axios from 'axios';
import {
    describe,
    expect,
    it,
    vi,
} from 'vitest';
import { getApiErrorMessage } from '@/services/errors';

describe('getApiErrorMessage', () => {
    it('returns the first validation error', () => {
        vi.spyOn(
            axios,
            'isAxiosError',
        ).mockReturnValue(true);

        const exception = {
            response: {
                data: {
                    message: 'Validation failed.',
                    errors: {
                        email: [
                            'Adresa de email nu este validă.',
                        ],
                        password: [
                            'Parola este obligatorie.',
                        ],
                    },
                },
            },
        };

        const result = getApiErrorMessage(
            exception,
            'Eroare necunoscută.',
        );

        expect(result).toBe(
            'Adresa de email nu este validă.',
        );
    });

    it('returns the API message when validation errors are missing', () => {
        vi.spyOn(
            axios,
            'isAxiosError',
        ).mockReturnValue(true);

        const exception = {
            response: {
                data: {
                    message: 'Acces interzis.',
                },
            },
        };

        const result = getApiErrorMessage(
            exception,
            'Eroare necunoscută.',
        );

        expect(result).toBe('Acces interzis.');
    });

    it('returns the fallback for a non-Axios error', () => {
        vi.spyOn(
            axios,
            'isAxiosError',
        ).mockReturnValue(false);

        const result = getApiErrorMessage(
            new Error('Network error'),
            'Eroare necunoscută.',
        );

        expect(result).toBe('Eroare necunoscută.');
    });
});
