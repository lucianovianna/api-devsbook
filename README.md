# Devsbook API

## Endpoints


- '^' acessivel sem login
<br>
 


POST ^api/auth/login (email, password)

POST api/auth/logout

POST api/auth/refresh

<hr>

POST ^api/user (name, email, password, birthdate)

PUT api/user (name, email, birthdate, city, work, password, password_confirm)

<hr>

POST api/user/avatar (avatar)

POST api/user/cover (cover)

<hr>

GET api/feed (page)

GET api/user/feed (page)

GET api/user/:id/feed (page)

GET api/user/photos (page)

GET api/user/:id/photos (page)

POST api/user/:id/follow

GET api/user/:id/followers

<hr>

GET api/user

GET api/user/:id

<hr>

POST api/feed (type=text/photo, body, photo)

<hr>

POST api/post/:id/like

POST api/post/:id/comment (txt)

<hr>

GET api/search (txt)







