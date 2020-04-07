const jwt  = require('jsonwebtoken');
//const('dotenv').config();
const User = require('../models/user');
const expressJwt  = require('express-jwt');

exports.signin = (req,res) => {
    // Find the user based on email
    const {email,password} = req.body;
    User.findOne({email},(err,user)=>{
        // If error or no user
        if(err || !user){
            return res.status(401).json({
                error : "User with that email does not exist. Please signin."
            });
        }
        // If user is found make sure email and password matches
        // Create authenticate method in model and use here
        if(!user.adminAuthenticate(password)){
            return res.status(401).json({
                error : "Email and password do not match or you dont have access"
            });
        }

        // Generate a token based on user id and secret
        const token = jwt.sign({_id:user._id},process.env.JWT_SECRET);

        // Persist the token as 't' in cookie with expiry date
        res.cookie("t", token, {expire:new Date()+9999});

        // Return response with user and token to frontend client
        const {_id,name,email} = user;
        return res.json({token,user:{_id,name,email}});
    });

};

exports.signout = (req,res) => {
    res.clearCookie("t");
    return res.json({message:"signout success!"});
};

exports.requireSignin = expressJwt({
    secret:process.env.JWT_SECRET
});
