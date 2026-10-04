import { Form, Head } from '@inertiajs/react';
import DemoController from '@/actions/App/Http/Controllers/DemoController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Credentials = { email: string; password: string };

type Props = {
    status?: string;
    canResetPassword: boolean;
    demoEnabled: boolean;
    demo: { admin: Credentials; client: Credentials } | null;
};

export default function Login({
    status,
    canResetPassword,
    demoEnabled,
    demo,
}: Props) {
    return (
        <>
            <Head title="Log in" />

            {status && (
                <p
                    role="status"
                    className="rounded-md border border-border bg-muted px-3 py-2 text-center text-sm font-medium"
                >
                    {status}
                </p>
            )}

            {demo && (
                <div
                    role="status"
                    className="space-y-2 rounded-md border-2 border-primary px-4 py-3 text-sm"
                >
                    <p className="font-semibold">
                        Your demo studio is ready for 24 hours.
                    </p>
                    <p>
                        We filled in the studio login below. The client can log
                        in with:
                    </p>
                    <dl className="grid grid-cols-[auto_1fr] gap-x-3 font-mono text-xs">
                        <dt className="text-muted-foreground">Email</dt>
                        <dd className="break-all">{demo.client.email}</dd>
                        <dt className="text-muted-foreground">Password</dt>
                        <dd className="break-all">{demo.client.password}</dd>
                    </dl>
                    <p className="text-muted-foreground">
                        Or use "View as client" once you're in.
                    </p>
                </div>
            )}

            <Form
                key={demo?.admin.email ?? 'login'}
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus={!demo}
                                autoComplete="email"
                                placeholder="email@example.com"
                                defaultValue={demo?.admin.email}
                                aria-invalid={errors.email ? true : undefined}
                                aria-describedby={
                                    errors.email ? 'email-error' : undefined
                                }
                            />
                            <InputError
                                id="email-error"
                                message={errors.email}
                            />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Password</Label>
                                {canResetPassword && !demo && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                    >
                                        Forgot your password?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autoComplete="current-password"
                                placeholder="Password"
                                defaultValue={demo?.admin.password}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox id="remember" name="remember" />
                            <Label htmlFor="remember">Remember me</Label>
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            disabled={processing}
                            data-test="login-button"
                            data-tour="login-submit"
                            autoFocus={!!demo}
                        >
                            {processing && <Spinner />}
                            Log in
                        </Button>
                    </div>
                )}
            </Form>

            {demoEnabled && !demo && (
                <Form
                    {...DemoController.store.form()}
                    className="space-y-3 border-t border-border pt-6 text-center"
                >
                    {({ processing }) => (
                        <>
                            <p className="text-sm text-muted-foreground">
                                Just looking around? Get your own demo studio,
                                no sign-up needed.
                            </p>
                            <Button
                                type="submit"
                                variant="secondary"
                                className="w-full"
                                disabled={processing}
                                data-tour="take-the-controls"
                            >
                                {processing && <Spinner />}
                                Take the controls
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
