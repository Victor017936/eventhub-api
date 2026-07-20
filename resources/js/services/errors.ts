import axios from 'axios';

interface ApiErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}

export function getApiErrorMessage(
    exception: unknown,
    fallbackMessage: string,
): string {
    if (! axios.isAxiosError<ApiErrorResponse>(exception)) {
        return fallbackMessage;
    }

    const responseData = exception.response?.data;

    if (responseData?.errors) {
        const firstError = Object.values(responseData.errors)
            .flat()
            [0];

        if (firstError) {
            return firstError;
        }
    }

    return responseData?.message ?? fallbackMessage;
}

