const express = require('express');
const app = express();
const port = 3000;

// Middleware to serve static files like CSS, JS, etc.
app.use(express.static('public'));
app.use(express.urlencoded({ extended: true }));

// Route for the login page
app.get('/', (req, res) => {
   res.sendFile(__dirname + '/login.html'); // Assuming you have a login.html file
});

// Handle login POST request (you’ll connect this to your login form)
app.post('/login', (req, res) => {
   const { username, password } = req.body;
   // Authentication logic here
   if (username === 'admin' && password === 'admin123') {
      res.redirect('/dashboard');
   } else {
      res.send('Login failed!');
   }
});

// Route for the dashboard page (display class list)
app.get('/dashboard', (req, res) => {
   // Render a sample response for now
   res.send('<h1>Welcome to the Class List Dashboard</h1>');
});

app.listen(port, () => {
   console.log(`Server is running at http://localhost:${port}`);
});
