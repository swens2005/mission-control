import { Form } from '@inertiajs/react';
import {
    FormField,
    focusFirstError,
    selectClassName,
} from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Option, OrganizationOption, Project } from '@/types';

type FormDefinition = { action: string; method: 'post' | 'put' };

export function ProjectForm({
    form,
    project,
    organizations,
    phases,
    organizationId,
    submitLabel,
}: {
    form: FormDefinition;
    project?: Project;
    organizations: OrganizationOption[];
    phases: Option[];
    organizationId?: number | null;
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
                    <FormField
                        name="organization_id"
                        label="Client"
                        error={errors.organization_id}
                    >
                        {(props) => (
                            <select
                                {...props}
                                required
                                className={selectClassName}
                                defaultValue={
                                    project?.organization.id ??
                                    organizationId ??
                                    ''
                                }
                            >
                                <option value="" disabled>
                                    Choose a client
                                </option>
                                {organizations.map((organization) => (
                                    <option
                                        key={organization.id}
                                        value={organization.id}
                                    >
                                        {organization.name}
                                    </option>
                                ))}
                            </select>
                        )}
                    </FormField>

                    <FormField name="name" label="Name" error={errors.name}>
                        {(props) => (
                            <Input
                                {...props}
                                required
                                defaultValue={project?.name}
                            />
                        )}
                    </FormField>

                    <FormField
                        name="description"
                        label="Description"
                        hint="Optional. What the site is for, in a sentence or two."
                        error={errors.description}
                    >
                        {(props) => (
                            <textarea
                                {...props}
                                rows={4}
                                className="w-full rounded-md border border-input bg-card px-3 py-2 text-sm shadow-xs"
                                defaultValue={project?.description ?? ''}
                            />
                        )}
                    </FormField>

                    <FormField name="phase" label="Phase" error={errors.phase}>
                        {(props) => (
                            <select
                                {...props}
                                required
                                className={selectClassName}
                                defaultValue={project?.phase ?? 'scope'}
                            >
                                {phases.map((phase) => (
                                    <option
                                        key={phase.value}
                                        value={phase.value}
                                    >
                                        {phase.label}
                                    </option>
                                ))}
                            </select>
                        )}
                    </FormField>

                    <FormField
                        name="target_launch_on"
                        label="Target launch date"
                        hint="Optional."
                        error={errors.target_launch_on}
                    >
                        {(props) => (
                            <Input
                                {...props}
                                type="date"
                                className="w-48 max-w-full"
                                defaultValue={project?.targetLaunchOn ?? ''}
                            />
                        )}
                    </FormField>

                    <Button disabled={processing}>{submitLabel}</Button>
                </>
            )}
        </Form>
    );
}
