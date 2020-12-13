A music search module that search for Songs, Albums and Artists using the Spotify API and Discogs API as a data source.

**Admin account: ron:ron (user:pass)**

On install, two new menu items are added, Music Search and API Key Configuration.

Spotify API ID:Secret and Discogs Personal access token are required for this module to be able to work and are edited under API Key Configuration.

When API Keys have been added, the music search functionality is available under /music-search

You search for a Song, Album or Artist, select the preferred data and submit the data and a new node is created for the selected content type.

Content types:

**Song**

Field Album, entity reference to Album

Field Title, text

Field Genre, text

Field Length, text


**Artist**

Field Title, text

Field Description, text

Field Images, Image

Field URL, Link


**Album**

Field Title, text

Field Album Cover, Image

Field Artist, text

Field Genre, text

Field Songs, List (text)

Field Year, text

