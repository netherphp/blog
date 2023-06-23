#!/bin/bash

ndb sql-create Nether.Blog.Blog --drop -y
ndb sql-create Nether.Blog.BlogUser --drop -y
ndb sql-create Nether.Blog.Post --drop -y
