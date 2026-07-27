<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Team Application</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1a0a2e; background: #f9fafb; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: white; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; text-transform: uppercase; font-style: italic;">New Team Application</h2>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 6px 0; color: #6b7280; width: 140px;">Role</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $application->role_label }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6b7280;">Name</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $application->name }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6b7280;">Email</td>
                <td style="padding: 6px 0;"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6b7280;">Discord</td>
                <td style="padding: 6px 0;">{{ $application->discord ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6b7280;">Platform(s)</td>
                <td style="padding: 6px 0;">{{ $application->platforms ? implode(', ', $application->platforms) : '—' }}</td>
            </tr>
        </table>

        <p style="color: #6b7280; margin-bottom: 4px; margin-top: 20px;">Motivation</p>
        <p style="white-space: pre-line; background: #f9fafb; border: 1px solid #f3f4f6; border-radius: 8px; padding: 12px;">{{ $application->motivation }}</p>

        <p style="margin-top: 24px;">
            <a href="{{ route('admin.applications.show', $application) }}"
               style="background: #7c3aed; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; display: inline-block;">
                View In Admin
            </a>
        </p>
    </div>
</body>
</html>
