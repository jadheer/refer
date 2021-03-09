const express = require('express');
const app = express();
const mongoose = require("mongoose");
const bodyParser = require('body-parser');
encoder = bodyParser.urlencoded({ extended: true });
jsonParsor = bodyParser.json();
const User = require('./models/user');

app.set('view engine', 'ejs');

mongoose.connect('mongodb+srv://jadheer:ShuvtX7MJuWXgmqY@cluster0.vix6f.mongodb.net/testing?retryWrites=true&w=majority',
    {
        useNewUrlParser: true,
        useUnifiedTopology: true,
    }
).then(() => {
    console.warn("db connected");
})

app.post('/login', encoder, function (req, res) {
    console.warn(req.body);
    res.render("Login");
});

app.get('/login', function (req, res) {
    res.render("Login");
});

app.get('/users', function (req, res) {
    User.find({}, function (err, users) {
        if (err) console.warn(err);
        console.warn(users);
    });
});

app.listen(4500);

// const data = new User({
//     _id:new mongoose.Types.ObjectId,
//     name:"Abdulla",
//     email:"abdulla@gmail.com",
//     address:"ADLA house"
// });
// data.save().then((result)=>{
//     console.warn(result);
// })
// .catch(err=>console.warn(err));

// User.find({},function(err,users){
//     if(err) console.warn(err);
//     console.warn(users);
// });
