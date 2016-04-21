# Shared-Files Daemon of Baidu File Cloud

by 虹原翼

So far, [Repository of Gentleman](http://galacg.me/) is using the this sharing-replacement program.

(I'll be glad if you can help me translate README.md into English)
(Reply: You said that you want someone help you translate this documentation, then I did.)

## Functions：

- Monitor the established shared-file, and if the shared-file has been detonated when attempt to access it by using the jumping-link, then the system will try to repair it automatically without changing the passcode of it.

- Repair the file by changing MD5 and this is a fundamental solution to the problem that Baidu doesn't allow the shared-content.

- Customize the passcode.

- By modifying the configuration file, the system can provide the download-url directly without visiting the extracting-page and sharing-page. And the video can be played online. Online-playing need HTTPS protocol while accessing the jumping-link.

## Installation Guide：

- Import the ``install.sql`` to the database.

- Modify ``config.sample.php`` to configure the database and modify ``$jumper`` to the location of ``jump.php``. If necessary, follow the instructions to modify the additional configurations.

- Copy ``config.sample.php`` to the directory ``bd`` and ``bd-admin`` respectively, and modify the name of it to ``config.php``.

- Bind ``bd`` and ``bd-admin`` to different websites, and add a HTTP Authentication Sequence to the latter(because we didn't develop the administrators' login function).

- Close``open_basedir``, and if your PHP version if 5.3, then close ``safe_mode``, too.

## Updating Guide：

- Replace files directly.

- Access to any record that has been added to the replacement, and the database will be updated automatically.

## Operation Guide

- Open the administrators' page and click "Browse Files" -> "Add User" to add the Baidu User that will be monitored. (WARNING: User account Cookie will be stored in the database unencrypted)

- Add new file in the "Browse Files" or use "Add File" on the homepage to add the link that user have already shared. (It must be the shared-file that user have already added.)

- Browse the records that you have added, and you can remove the useless record.

- Access the jumping-link on the homepage, then you can check if the record has been hooked automatically. If it has done, then the system will jump to the download page. And you need to provide this url to the visitor in the productive environment.

- If you enable the "Direct Link" function, the jumping-link will jump to the download-url. And you need to click "Go To the Extracting Page" then the system will check if the link is disabled.

## About the PassCode

The system will attempt to request the passcode while you're adding the record in "Browse Files".

The passcode can be any characters that the length is totally 4 but it cannot include any double-byte character.

For Example:

abcd (Legal)

abc (Sharing-sequence failed)

猫C (Legal)

猫 (Sharing-sequence failed)

μ's (There's a double-byte character so the sharing-sequence will complete successfully but you cannot extract files) **The system cannot check those kinds of passcode**, so please take care of it.

μμ (There's a double-byte character so the sharing-sequence will complete successfully but you cannot extract files) **The system cannot check those kinds of passcode**, so please take care of it.
