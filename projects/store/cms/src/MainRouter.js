import React from 'react';

import {Route,Switch} from 'react-router-dom';

import Home from './core/Home';
import Menu from './core/Menu';
import Signup from './user/Signup';
import Signin from './user/Signin';
import Listing from './products/Listing';
import Add from './products/Add';
import Edit from './products/Edit';

const MainRouter = () => (
    <div>
        <Menu />
        <Switch>
            <Route exact path="/" component={Home}></Route>
            <Route exact path="/signup" component={Signup}></Route>
            <Route exact path="/signin" component={Signin}></Route>
            <Route exact path="/products/listing" component={Listing}></Route>
            <Route exact path="/product/add" component={Add}></Route>
            <Route exact path="/product/edit/:id" component={Edit}></Route>
        </Switch>
    </div>
);

export default MainRouter;
