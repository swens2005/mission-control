import { Form } from '@inertiajs/react';
import { FormField, focusFirstError } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Organization } from '@/types';

type FormDefinition = { action: string; method: 'post' | 'put' };

export function OrganizationForm({
    form,
    organization,
    submitLabel,
}: {
    form: FormDefinition;
    organization?: Organization;
    submitLabel: string;
}) {
    return (
        <Form
            {...form}
            onError={focusFirstError}
            className="max-w-xl space-y-6"
        >
            {({ processing, errors }) => (
                <>
                    <FormField name="name" label="Name" error={errors.name}>
                        {(props) => (
                            <Input
                                {...props}
                                required
                                autoComplete="organization"
                                defaultValue={organization?.name}
                            />
                        )}
                    </FormField>

                    <FormField
                        name="website_url"
                        label="Website"
                        hint="Optional. For example https://example.com"
                        error={errors.website_url}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                type="url"
                                inputMode="url"
                                autoComplete="url"
                                defaultValue={organization?.websiteUrl ?? ''}
                            />
                        )}
                    </FormField>

                    <Button disabled={processing}>{submitLabel}</Button>
                </>
            )}
        </Form>
    );
}
