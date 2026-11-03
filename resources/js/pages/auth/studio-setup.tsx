import { Form, Head } from '@inertiajs/react';
import StudioSetupController from '@/actions/App/Http/Controllers/StudioSetupController';
import { FormField, focusFirstError } from '@/components/form-field';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

/**
 * One-time setup of the first real studio (story 41, ADR 0013). The server
 * only shows this page while setup is open.
 */
export default function StudioSetup() {
    return (
        <>
            <Head title="Set up your studio" />

            <Form
                {...StudioSetupController.store.form()}
                resetOnError={['token', 'password', 'password_confirmation']}
                onError={focusFirstError}
                className="grid gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <FormField
                            name="token"
                            label="Setup token"
                            hint="The token you made on your own computer. It works once."
                            error={errors.token}
                        >
                            {(props) => (
                                <PasswordInput
                                    {...props}
                                    required
                                    autoComplete="off"
                                    autoFocus
                                />
                            )}
                        </FormField>

                        <FormField
                            name="studio"
                            label="Studio name"
                            error={errors.studio}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    required
                                    maxLength={255}
                                    autoComplete="organization"
                                />
                            )}
                        </FormField>

                        <FormField
                            name="name"
                            label="Your name"
                            error={errors.name}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    required
                                    maxLength={255}
                                    autoComplete="name"
                                />
                            )}
                        </FormField>

                        <FormField
                            name="email"
                            label="Email address"
                            hint="You'll log in with this."
                            error={errors.email}
                        >
                            {(props) => (
                                <Input
                                    {...props}
                                    type="email"
                                    required
                                    maxLength={255}
                                    autoComplete="email"
                                />
                            )}
                        </FormField>

                        <FormField
                            name="password"
                            label="Password"
                            hint="At least 12 characters, with upper and lower case, a number and a symbol."
                            error={errors.password}
                        >
                            {(props) => (
                                <PasswordInput
                                    {...props}
                                    required
                                    autoComplete="new-password"
                                />
                            )}
                        </FormField>

                        <FormField
                            name="password_confirmation"
                            label="Password again"
                            error={errors.password_confirmation}
                        >
                            {(props) => (
                                <PasswordInput
                                    {...props}
                                    required
                                    autoComplete="new-password"
                                />
                            )}
                        </FormField>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            Create my studio
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

StudioSetup.layout = {
    title: 'Set up your studio',
    description:
        'A one-time page for the first real studio. It closes once the studio exists.',
};
