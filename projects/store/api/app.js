const express           = require('express');
const app               = express();
const morgan            = require("morgan");
const mongoose          = require("mongoose");
const dotenv            = require("dotenv");
const bodyParser        = require('body-parser');
const expressValidator  = require('express-validator');
const cors              = require("cors");

dotenv.config();

//db
mongoose.connect(process.env.MONGO_URI, { useUnifiedTopology: true , useNewUrlParser: true })
.then(()=>console.log("DB Connected"));

mongoose.connection.on('error',err=>{
    console.log(`DB connection error:${err.message}`);
});

// Routes
const authRoutes        = require('./routes/auth');
const userRoutes        = require('./routes/user');
const adminAuthRoutes   = require('./routes/adminAuth');
const productRoutes     = require('./routes/products');

//Middleware
app.use(morgan('dev'));
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(expressValidator());
app.use(cors());

app.use("/",productRoutes);
app.use("/",adminAuthRoutes);
app.use("/",userRoutes);
app.use("/",authRoutes);
app.use(function (err, req, res, next) {
  if (err.name === 'UnauthorizedError') {
    res.status(401).json({error:"Unauthorized!"});
  }
});

const port = process.env.PORT || 8089;
app.listen(port,() =>{
    console.log(`a node js is listening on port ${port}`);
});
