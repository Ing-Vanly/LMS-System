# LMS Media Storage Flow

This document explains how the LMS stores and displays learning material files such as videos, PDFs, and audiobooks.

The main idea is simple:

```text
Real files live in AWS S3.
The database stores only metadata and the S3 path/key.
Laravel controls who can upload and view files.
The browser uploads and opens files using temporary signed URLs.
```

## Current AWS Setup

```text
S3 bucket: lms-system-ing-vanly
AWS region: ap-southeast-1
Laravel media disk: s3
```

The AWS access key and secret key must stay only in `.env`. Never put them in React, GitHub, screenshots, or documentation.

## Admin Upload Flow

This is the upload flow used by the Learning Materials create and edit pages.

```text
1. Admin chooses a video, PDF, or audiobook in the frontend.

2. React sends file information to Laravel:
   - category_id
   - material type
   - file name
   - file size
   - mime type

3. Laravel checks:
   - the admin is logged in
   - the category exists
   - the file extension/mime type matches the selected material type
   - the file size is allowed

4. Laravel creates an S3 path/key:
   learning-materials/{category-slug}/{type}/{uuid}.{extension}

5. Laravel creates a temporary upload URL for that exact S3 path.

6. React uploads the file directly from the browser to AWS S3.

7. React sends the uploaded S3 path back to Laravel.

8. Laravel checks that the file exists in S3 and that the size matches.

9. Laravel saves the learning material record in the database.
```

Short version:

```text
Admin browser -> Laravel: request upload permission
Laravel -> Admin browser: temporary upload URL
Admin browser -> S3: upload real file
Admin browser -> Laravel: save material metadata
Laravel -> Database: save path and file info
```

## What S3 Stores

S3 stores the real uploaded file.

Example S3 object keys:

```text
learning-materials/exam-preparation/video/550e8400-e29b-41d4-a716-446655440000.mp4
learning-materials/exam-preparation/pdf/550e8400-e29b-41d4-a716-446655440001.pdf
learning-materials/listening-practice/audiobook/550e8400-e29b-41d4-a716-446655440002.mp3
```

S3 folders are not real folders like Windows folders. They are prefixes inside an object key. AWS shows them like folders to make the bucket easier to browse.

## What The Database Stores

The database does not store the real file content.

The database stores metadata like this:

```text
category_id
title
slug
description
type
status
disk
path
original_name
mime_type
extension
size_bytes
published_at
```

Example:

```text
title: Introduction Video
type: video
disk: s3
path: learning-materials/exam-preparation/video/550e8400-e29b-41d4-a716-446655440000.mp4
original_name: intro.mp4
mime_type: video/mp4
extension: mp4
size_bytes: 248000000
```

Important rule:

```text
Store the S3 path/key in the database.
Do not store temporary URLs in the database.
```

Temporary URLs expire, so Laravel should create a fresh one whenever a user opens a file.

## Admin Edit Flow

When admin edits only the title, description, category, type, or status:

```text
React -> Laravel: update metadata
Laravel -> Database: update record
```

When admin replaces the file:

```text
1. React asks Laravel for a new temporary upload URL.
2. React uploads the new file directly to S3.
3. React sends the new S3 path to Laravel.
4. Laravel verifies the new file exists.
5. Laravel updates the database record.
6. Laravel deletes the old file from S3.
```

## Admin Delete Flow

When admin deletes a learning material:

```text
1. Laravel deletes the file from S3.
2. Laravel deletes the database record.
```

This keeps S3 clean so old files do not stay in the bucket after the material is removed.

## Student/User Frontend Flow

The student frontend should not use the raw S3 path directly.

Recommended student flow:

```text
1. Student logs in.

2. Student opens the learning materials page.

3. Frontend asks Laravel for available/published materials.

4. Laravel reads the database and returns material data:
   - title
   - description
   - type
   - category
   - size
   - preview_url

5. Student clicks Watch, Read, Listen, or Open File.

6. Frontend opens the Laravel preview URL:
   /learning-materials/{id}/preview

7. Laravel checks if the student can view this material.

8. Laravel creates a temporary view URL for the S3 file.

9. Student browser loads the file from AWS S3.
```

Short version:

```text
Student frontend -> Laravel: ask to view file
Laravel -> S3: create temporary view URL
Student browser -> S3: open/watch/read/listen
```

For PDFs, the frontend can open the preview URL in a new tab.

For videos, the frontend can use the preview URL as the video source.

For audiobooks, the frontend can use the preview URL as the audio source.

Example:

```tsx
<video controls src={material.preview_url} />
<audio controls src={material.preview_url} />
```

If the material is private, always ask Laravel for the view URL first. Do not expose permanent public S3 URLs.

## Keywords

### S3

Amazon S3 is AWS storage. It stores the real media files, such as videos, PDFs, and audio files.

### Bucket

A bucket is a storage container in S3.

For this project:

```text
lms-system-ing-vanly
```

### Object

An object is one file stored in S3.

Example:

```text
learning-materials/exam-preparation/pdf/file.pdf
```

### S3 Path / S3 Key / Object Key

These mean almost the same thing in this project.

It is the path used to find the file inside the S3 bucket.

Example:

```text
learning-materials/exam-preparation/pdf/550e8400-e29b-41d4-a716-446655440001.pdf
```

This value is stored in the database as `path`.

### Metadata

Metadata is information about the file, not the real file content.

Examples:

```text
title
description
type
original file name
file size
mime type
extension
S3 path
```

### Temporary Upload URL

A temporary upload URL is a short-time signed URL that lets the browser upload one file directly to S3.

Laravel creates it. React uses it.

```text
Laravel creates temporary upload URL
React uploads file directly to S3
URL expires after a short time
```

This keeps AWS credentials safe because the browser never receives the AWS secret key.

### Temporary View URL

A temporary view URL is a short-time signed URL that lets a student open a private S3 file.

Laravel creates it when the user opens the material.

```text
Student clicks Open
Laravel checks permission
Laravel creates temporary view URL
Student browser opens S3 file
URL expires later
```

This is why the database stores the S3 path, not the full URL.

### Presigned URL

A presigned URL is an AWS signed URL. It gives temporary permission for one action.

Common actions:

```text
PUT object: upload a file
GET object: view/download a file
```

In this project:

```text
Temporary upload URL = presigned PUT URL
Temporary view URL = presigned GET URL
```

### CORS

CORS means Cross-Origin Resource Sharing.

Because the browser uploads directly to S3, S3 must allow requests from the LMS website origin.

Example local origins:

```text
http://127.0.0.1:8000
http://127.0.0.1:8001
http://localhost:8000
http://localhost:8001
```

CORS does not make the bucket public. It only allows the browser from your LMS site to use temporary signed URLs.

### Private Bucket

A private bucket means users cannot freely open files from S3.

This is good for an LMS because course files should be controlled by the application.

Recommended:

```text
Keep Block Public Access ON.
Use temporary signed URLs for upload and view.
```

### CloudFront

CloudFront is AWS CDN for faster delivery.

S3 stores the files.

CloudFront delivers the files faster to users.

For future production:

```text
S3 = storage
CloudFront = faster viewing/streaming
Laravel = permission and database
```

CloudFront is most useful when many students watch videos or users are in different countries.

### Multipart Upload

Multipart upload splits a large file into many parts and uploads them separately.

Current implementation:

```text
Single direct upload up to the app limit
Current app limit: 500 MB
```

Future upgrade for very large videos:

```text
Use multipart upload for 1 GB, 2 GB, or larger videos.
```

Multipart upload is better for large videos because failed parts can be retried without restarting the whole upload.

## Current Code Flow

### Upload Intent Route

```text
POST /learning-materials/uploads
```

Laravel returns:

```text
upload.method
upload.url
upload.headers
upload.path
upload.expires_at
```

React then uploads the selected file to `upload.url`.

### Save Material Route

Create:

```text
POST /learning-materials
```

Update:

```text
POST /learning-materials/{id}
_method=PUT
```

React sends:

```text
category_id
title
description
type
status
upload_path
```

Laravel checks `upload_path`, confirms the file exists in S3, then saves the database record.

### Preview Route

```text
GET /learning-materials/{id}/preview
```

Laravel creates a temporary view URL and redirects the browser to it.

The frontend should use this route instead of building S3 URLs manually.

## Best Practice Summary

```text
Do:
- Store real files in S3.
- Store only path and metadata in database.
- Keep the S3 bucket private.
- Use temporary upload URLs for admin uploads.
- Use temporary view URLs for student access.
- Add S3 CORS for browser direct upload.
- Use CloudFront later for faster video delivery.
- Use multipart upload later for very large videos.

Do not:
- Store AWS keys in React.
- Store temporary URLs in the database.
- Make the S3 bucket public for LMS content.
- Send large videos through Laravel if S3 direct upload is available.
```

