
async function setAll() {
    try{
        const User = require('./models/user');
        const mongoose = require("mongoose");
        const dotenv = require("dotenv");
        dotenv.config();

        //db
        await mongoose.connect(process.env.MONGO_URI, { useUnifiedTopology: true , useNewUrlParser: true })
        .then(()=>console.log("DB Connected"));

        mongoose.connection.on('error',err=>{
            console.log(`DB connection error:${err.message}`);
        });
    }
    catch(e){
        console.log(e);
    }
}


async function seedAdmin() {
    try{

        var email       = 'admin@gmail.com';
        var name        = 'Admin';
        var isAdmin     = 1;
        var password    = 'admin123@#';

        const userExists = await User.findOne({email:email});
        if(userExists){
            console.log('Already seeded!');
        }
        else{
            console.log('Processing');
            const user = await new User({
                email:email,
                name:name,
                isAdmin:isAdmin,
                password:password
            });
            await user.save();
        }
        console.log('Exiting');
        process.exit();
    }
    catch(e){
        console.log(e);
    }
}

setAll();
seedAdmin();
