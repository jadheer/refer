const express  = require('express');
const bodyParser      = require('body-parser');
const cookieParser      = require('cookie-parser');
const app = express();
const expressValidator      = require('express-validator');
const morgan = require("morgan");
const dotenv = require("dotenv");
const mongoose = require("mongoose");
const cors = require("cors");

dotenv.config();

//db
mongoose.connect(process.env.MONGO_URI, { useUnifiedTopology: true , useNewUrlParser: true })
.then(()=>console.log("DB Connected"));

mongoose.connection.on('error',err=>{
    console.log(`DB connection error:${err.message}`);
});

// Bring in routes
const postRoutes = require('./routes/post');
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/user');

//Middleware
app.use(morgan('dev'));
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(cookieParser());
app.use(expressValidator());
app.use(cors());
app.use("/",postRoutes);
app.use("/",authRoutes);
app.use("/",userRoutes);
app.use(function (err, req, res, next) {
  if (err.name === 'UnauthorizedError') {
    res.status(401).json({error:"Unauthorized!"});
  }
});

const port = process.env.PORT || 8089;
app.listen(port,() =>{
    console.log(`a node js is listening on port ${port}`);
});
